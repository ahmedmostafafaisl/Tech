<?php

namespace App\Services\SubmitCompleteForm;

use App\Models\AppointmentFormSubmission;
use App\Models\AppointmentType;
use App\Models\AppointmentTypeOption;
use App\Models\DirectAppointment;
use App\Models\FormField;
use App\Models\OptionField;
use App\Repositories\Interfaces\AppointmentFormSubmissionRepositoryInterface;
use App\Services\DY365\DyService;
use App\Services\Dynamics\DynamicsAttachmentPayloadService;
use App\Services\Logs\TechnicianAppointmentLogService;
use App\Services\Payment\PaymentCompletionDispatcher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AppointmentFormSubmissionService
{
    public function __construct(
        private readonly AppointmentFormSubmissionRepositoryInterface $repo,
        private readonly DyService $dyService,
        private readonly DynamicsAttachmentPayloadService $dynamicsAttachmentPayloadService,
    ) {}

    // =========================================================================
    // PUBLIC — HTTP entry point
    // =========================================================================

    public function submit(array $validated, array $filesBag, ?int $userId): int
    {
        $logService   = app(TechnicianAppointmentLogService::class);
        $bookId       = $validated['book_id'] ?? null;
        $salesOrderId = $validated['sales_order_id'] ?? null;

        $requestPayloadForLog = $validated;
        unset($requestPayloadForLog['files']);

        try {
            Log::info('Submit validated payload', $validated);

            $directAppointment = DirectAppointment::query()
                ->where('sales_order_id', $salesOrderId)
                ->where('book_id', $bookId)
                ->orderByDesc('id')
                ->first();

            if (!$directAppointment) {
                $logService->validationFailed(
                    techId: auth()->user()?->tech_id,
                    action: 'submit_appointment_form',
                    bookId: $bookId,
                    salesOrderId: $salesOrderId,
                    message: 'No appointment found for this sales order and book.',
                    requestPayload: $requestPayloadForLog,
                    responsePayload: [
                        'sales_order_id' => 'No appointment found for this sales order and book.',
                        'book_id'        => 'No appointment found for this sales order and book.',
                    ],
                    userId: $userId,
                );

                throw ValidationException::withMessages([
                    'sales_order_id' => 'No appointment found for this sales order and book.',
                    'book_id'        => 'No appointment found for this sales order and book.',
                ]);
            }

            $validated['appointment_id'] = $directAppointment->id;

            $type = AppointmentType::query()
                ->where('code', $validated['type_code'])
                ->firstOrFail();

            $submissionId = DB::transaction(function () use (
                $validated,
                $filesBag,
                $userId,
                $type,
                $logService,
                $requestPayloadForLog,
                $bookId,
                $salesOrderId,
                $directAppointment
            ) {
                // ── Resolve option hierarchy ──────────────────────────────
                if (in_array($type->code, ['periodic', 'service'], true)) {
                    $rootOptionId = (int) $validated['option_id'];
                    $problemId    = null;
                    $solutionId   = null;

                    $this->assertOptionBelongsToType($rootOptionId, $type->id);
                    $targetOptionIdForFields = $rootOptionId;
                } else {
                    $rootOptionId = (int) $validated['device_id'];
                    $problemId    = (int) $validated['problem_id'];
                    $solutionId   = (int) $validated['solution_id'];

                    $this->assertEmergencyHierarchy($type->id, $rootOptionId, $problemId, $solutionId);
                    $targetOptionIdForFields = $solutionId;
                }

                // ── Load required fields for this option ──────────────────
                $requiredFields = OptionField::query()
                    ->where('option_id', $targetOptionIdForFields)
                    ->where('is_active', true)
                    ->pluck('is_required', 'field_id')
                    ->all();

                Log::info('Target option for fields', [
                    'type_code'                   => $type->code,
                    'target_option_id_for_fields' => $targetOptionIdForFields,
                ]);

                $fieldIds = array_keys($requiredFields);

                $fields = FormField::query()
                    ->whereIn('id', $fieldIds)
                    ->where('is_active', true)
                    ->get(['id', 'field_key', 'field_type', 'label_ar']);

                $valuesInput = $validated['values'] ?? [];

                Log::info('Resolved fields', [
                    'field_ids' => $fieldIds,
                    'fields'    => $fields->pluck('field_key')->all(),
                ]);

                // ── Validate required field presence ──────────────────────
                foreach ($fields as $field) {
                    $isReq = (bool) ($requiredFields[$field->id] ?? false);

                    if (!$isReq) {
                        continue;
                    }

                    if (in_array($field->field_type, ['image', 'file'], true)) {
                        $hasFile = isset($filesBag['files'][$field->field_key]);

                        if (!$hasFile) {
                            $logService->validationFailed(
                                techId: auth()->user()?->tech_id,
                                action: 'submit_appointment_form',
                                bookId: $bookId,
                                salesOrderId: $salesOrderId,
                                message: "Required file field missing: {$field->field_key}",
                                requestPayload: $requestPayloadForLog,
                                responsePayload: [
                                    "files.{$field->field_key}" => "Field {$field->field_key} is required.",
                                ],
                                userId: $userId,
                                meta: [
                                    'appointment_id' => $directAppointment->id,
                                    'field_id'       => $field->id,
                                    'field_key'      => $field->field_key,
                                ],
                            );

                            throw ValidationException::withMessages([
                                "files.{$field->field_key}" => "Field {$field->field_key} is required.",
                            ]);
                        }
                    } else {
                        $hasValue = array_key_exists($field->field_key, $valuesInput)
                            && $valuesInput[$field->field_key] !== null
                            && $valuesInput[$field->field_key] !== '';

                        if (!$hasValue) {
                            $logService->validationFailed(
                                techId: auth()->user()?->tech_id,
                                action: 'submit_appointment_form',
                                bookId: $bookId,
                                salesOrderId: $salesOrderId,
                                message: "Required value field missing: {$field->field_key}",
                                requestPayload: $requestPayloadForLog,
                                responsePayload: [
                                    "values.{$field->field_key}" => "Field {$field->field_key} is required.",
                                ],
                                userId: $userId,
                                meta: [
                                    'appointment_id' => $directAppointment->id,
                                    'field_id'       => $field->id,
                                    'field_key'      => $field->field_key,
                                ],
                            );

                            throw ValidationException::withMessages([
                                "values.{$field->field_key}" => "Field {$field->field_key} is required.",
                            ]);
                        }
                    }
                }

                // ── Persist submission ────────────────────────────────────
                $submission = $this->repo->createSubmission([
                    'sales_order_id'      => $validated['sales_order_id'] ?? null,
                    'book_id'             => $validated['book_id'] ?? null,
                    'appointment_id'      => $validated['appointment_id'] ?? null,
                    'appointment_type_id' => $type->id,
                    'root_option_id'      => $rootOptionId,
                    'problem_option_id'   => $problemId,
                    'solution_option_id'  => $solutionId,
                    'submitted_by'        => $userId,
                ]);

                // ── Upload files + build value rows ───────────────────────
                $rows = [];

                foreach ($fields as $field) {
                    $row = [
                        'submission_id' => $submission->id,
                        'field_id'      => $field->id,
                        'value_text'    => null,
                        'value_number'  => null,
                        'value_boolean' => null,
                        'value_json'    => null,
                    ];

                    if (in_array($field->field_type, ['image', 'file'], true)) {
                        $uploaded = $this->normalizeFiles($filesBag['files'][$field->field_key] ?? null);

                        if (!empty($uploaded)) {
                            $paths = [];

                            foreach ($uploaded as $file) {
                                $paths[] = $file->store("appointment-forms/{$type->code}", 's3');
                            }

                            $row['value_json'] = $paths;
                        }
                    } else {
                        $val = $valuesInput[$field->field_key] ?? null;

                        if ($val !== null) {
                            if ($field->field_type === 'number') {
                                $row['value_number'] = (float) $val;
                            } elseif ($field->field_type === 'boolean') {
                                $row['value_boolean'] = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                            } else {
                                $row['value_text'] = (string) $val;
                            }
                        }
                    }

                    $rows[] = $row;
                }

                $this->repo->upsertValues($submission->id, $rows);

                // ── Fire background Dynamics command after commit ─────────
                // DB is fully committed before the command process starts,
                // so it can safely read the submission row.
                DB::afterCommit(function () use ($submission) {
                    $cmd = 'php ' . escapeshellarg(base_path('artisan'))
                        . ' appointments:send-submission-dynamics '
                        . escapeshellarg((string) $submission->id)
                        . ' > /dev/null 2>&1 &';

                    exec($cmd);
                });

                // ── Log success ───────────────────────────────────────────
                $logService->success(
                    techId: auth()->user()?->tech_id,
                    action: 'submit_appointment_form',
                    bookId: $bookId,
                    salesOrderId: $salesOrderId,
                    message: 'Appointment form submitted successfully',
                    requestPayload: $requestPayloadForLog,
                    responsePayload: [
                        'submission_id' => $submission->id,
                    ],
                    userId: $userId,
                    meta: [
                        'appointment_id'        => $directAppointment->id,
                        'appointment_type_id'   => $type->id,
                        'appointment_type_code' => $type->code,
                        'root_option_id'        => $rootOptionId,
                        'problem_option_id'     => $problemId,
                        'solution_option_id'    => $solutionId,
                        'fields_count'          => count($rows),
                    ],
                );

                return $submission->id;
            });

            return $submissionId;
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $logService->failed(
                techId: auth()->user()?->tech_id,
                action: 'submit_appointment_form',
                bookId: $bookId,
                salesOrderId: $salesOrderId,
                message: 'submit appointment form failed',
                requestPayload: $requestPayloadForLog,
                error: $e->getMessage(),
                userId: $userId,
                meta: [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            );

            throw $e;
        }
    }

    // =========================================================================
    // PUBLIC — Command entry point (called by SendSubmissionToDynamics command)
    // =========================================================================

    /**
     * Loads the submission type then delegates to the internal Dynamics sender.
     * This is the only public entry point for the background command.
     */
    public function sendSubmissionToDynamics(int $submissionId): void
    {
        $submission = AppointmentFormSubmission::query()
            ->with('type:id,code')
            ->findOrFail($submissionId);

        $this->sendToDynamicsFromSubmission($submissionId, $submission->type->code);
    }

    // =========================================================================
    // PRIVATE — Dynamics dispatch
    // =========================================================================

    /**
     * Full flow after DB commit:
     *   1. Load submission + options
     *   2. Find + verify appointment is paid
     *   3. Collect notes + attachments
     *   4. Log payload
     *   5. Send to Dynamics via queueAppointmentPayload
     *   6. Dispatch payment completion
     */
    private function sendToDynamicsFromSubmission(int $submissionId, string $typeCode): void
    {
        try {
            // ── Step 1: Load submission with relationships ────────────────
            $submission = $this->repo->loadForDynamics($submissionId);
            $submission->load(['rootOption', 'problemOption', 'solutionOption']);

            $serviceNote = collect([
                optional($submission->rootOption)->label_ar,
                optional($submission->problemOption)->label_ar,
                optional($submission->solutionOption)->label_ar,
            ])->filter()->implode(' - ');

            // ── Step 2: Find appointment ──────────────────────────────────
            $appointment = DirectAppointment::query()
                ->where('sales_order_id', $submission->sales_order_id)
                ->where('book_id', $submission->book_id)
                ->orderByDesc('id')
                ->first();

            if (!$appointment) {
                Log::warning('Dynamics send skipped: appointment not found', [
                    'submission_id'  => $submissionId,
                    'sales_order_id' => $submission->sales_order_id,
                    'book_id'        => $submission->book_id,
                ]);
                return;
            }

            // ── Step 3: Guard — must be paid ──────────────────────────────
            if ($appointment->status !== 'paid') {
                Log::warning('Dynamics send skipped: appointment not paid', [
                    'submission_id'  => $submissionId,
                    'appointment_id' => $appointment->id,
                    'status'         => $appointment->status,
                ]);
                return;
            }

            // ── Step 4: Collect notes + attachments ───────────────────────
            $notes       = $this->collectAllNotes($submission) ?: 'Attachments submitted';
            $attachments = $this->collectAllImageAttachments($submission);

            // ── Step 5: Log payload before sending ────────────────────────
            Log::info('Dynamics payload ready to dispatch', [
                'submission_id'      => $submissionId,
                'type_code'          => $typeCode,
                'appointment_id'     => $appointment->id,
                'book_id'            => $appointment->book_id,
                'sales_order_id'     => $appointment->sales_order_id,
                'appointment_status' => $appointment->status,
                'service_note'       => $serviceNote,
                'note'               => $notes,
                'attachments_count'  => count($attachments),
                'attachments'        => $attachments,
            ]);

            // ── Step 6: Send to Dynamics ──────────────────────────────────
            $this->dynamicsAttachmentPayloadService->queueAppointmentPayload(
                appointment: $appointment,
                note: $notes,
                attachments: $attachments,
                serviceNote: $serviceNote,
            );

            Log::info('Dynamics payload dispatched successfully', [
                'submission_id'  => $submissionId,
                'appointment_id' => $appointment->id,
            ]);

            // ── Step 7: Dispatch payment completion ───────────────────────
            // Runs after Dynamics succeeds. Appointment is already loaded
            // and confirmed paid. Any failure here is caught below and logged
            // with a full trace so the exact failure point is visible.
            Log::info('Proceeding to payment completion dispatch', [
                'submission_id'  => $submissionId,
                'appointment_id' => $appointment->id,
            ]);

            $result = app(PaymentCompletionDispatcher::class)->dispatch($appointment);

            if (!($result['ok'] ?? false)) {
                Log::warning('Payment completion not dispatched after submission', [
                    'submission_id'  => $submissionId,
                    'appointment_id' => $appointment->id,
                    'result'         => $result,
                ]);
            } else {
                Log::info('Payment completion dispatched after submission', [
                    'submission_id'  => $submissionId,
                    'appointment_id' => $appointment->id,
                ]);
            }
        } catch (\Throwable $e) {
            // Full trace included so the exact line that threw is visible
            // in the log without needing to reproduce the issue.
            Log::error('sendToDynamicsFromSubmission failed', [
                'submission_id' => $submissionId,
                'type_code'     => $typeCode,
                'error'         => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'trace'         => $e->getTraceAsString(),
            ]);
        }
    }

    // =========================================================================
    // PRIVATE — Helpers
    // =========================================================================

    private function collectAllNotes($submission): string
    {
        $parts = [];

        foreach ($submission->values as $v) {
            $type = $v->field->field_type ?? null;

            if (!in_array($type, ['text', 'textarea', 'select'], true)) {
                continue;
            }

            $val = trim((string) $v->value_text);

            if ($val === '') {
                continue;
            }

            $label   = $v->field->label_ar ?: $v->field->field_key;
            $parts[] = "{$label}: {$val}";
        }

        return trim(implode("\n", $parts));
    }

    private function collectAllImageAttachments($submission): array
    {
        $attachments = [];

        foreach ($submission->values as $v) {
            $type = $v->field->field_type ?? null;

            if (!in_array($type, ['image', 'file'], true)) {
                continue;
            }

            $paths = $v->value_json ?? [];

            if (!is_array($paths)) {
                continue;
            }

            foreach ($paths as $path) {
                if (!$path) {
                    continue;
                }

                $url = Storage::disk('s3')->url($path);

                if (!empty($url)) {
                    $attachments[] = [
                        'URL'         => $url,
                        'Description' => $v->field->field_key ?? 'image',
                    ];
                }
            }
        }

        return array_values($attachments);
    }

    private function assertOptionBelongsToType(int $optionId, int $typeId): void
    {
        $exists = AppointmentTypeOption::query()
            ->where('id', $optionId)
            ->where('appointment_type_id', $typeId)
            ->exists();

        if (!$exists) {
            throw ValidationException::withMessages([
                'option_id' => 'Option does not belong to this appointment type.',
            ]);
        }
    }

    private function assertEmergencyHierarchy(int $typeId, int $deviceId, int $problemId, int $solutionId): void
    {
        Log::info('Checking emergency hierarchy', [
            'type_id'     => $typeId,
            'device_id'   => $deviceId,
            'problem_id'  => $problemId,
            'solution_id' => $solutionId,
        ]);

        $deviceOk = AppointmentTypeOption::query()
            ->where('id', $deviceId)
            ->where('appointment_type_id', $typeId)
            ->whereNull('parent_id')
            ->exists();

        if (!$deviceOk) {
            throw ValidationException::withMessages(['device_id' => 'Invalid device.']);
        }

        $problemOk = AppointmentTypeOption::query()
            ->where('id', $problemId)
            ->where('appointment_type_id', $typeId)
            ->where('parent_id', $deviceId)
            ->exists();

        if (!$problemOk) {
            throw ValidationException::withMessages(['problem_id' => 'Invalid problem for device.']);
        }

        $solutionOk = AppointmentTypeOption::query()
            ->where('id', $solutionId)
            ->where('appointment_type_id', $typeId)
            ->where('parent_id', $problemId)
            ->exists();

        if (!$solutionOk) {
            throw ValidationException::withMessages(['solution_id' => 'Invalid solution for problem.']);
        }
    }

    /** @return UploadedFile[] */
    private function normalizeFiles(mixed $input): array
    {
        if ($input instanceof UploadedFile) {
            return [$input];
        }

        if (is_array($input)) {
            return array_values(array_filter($input, fn($x) => $x instanceof UploadedFile));
        }

        return [];
    }
}
