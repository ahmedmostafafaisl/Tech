<?php

namespace App\Http\Controllers\Api\CompleteForm;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteForm\StoreCompleteFormRequest;
use App\Http\Requests\CompleteForm\UpdateCompleteFormRequest;
use App\Http\Resources\CompleteForm\CompleteFormResource;
use App\Models\DirectAppointment;
use App\Repositories\Interfaces\CompleteFormRepositoryInterface;
use App\Services\Dynamics\DynamicsAttachmentPayloadService;
use App\Services\Logs\TechnicianAppointmentLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CompleteFormController extends Controller
{
    public function __construct(
        private CompleteFormRepositoryInterface $repo,
        private DynamicsAttachmentPayloadService $dynamicsAttachmentPayloadService,
    ) {}

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $data = $this->repo->paginate($perPage);

        // ✅ data only (no meta/links)
        return response()->json([
            'data' => CompleteFormResource::collection($data->items()),
        ]);
    }




    public function store(StoreCompleteFormRequest $request)
    {
        $logService = app(TechnicianAppointmentLogService::class);

        $techId = auth()->user()?->tech_id;
        $requestPayloadForLog = $request->except([
            'home_salt_image',
            'device_salt_image',
            'carbon_depletion_image',
            'sink_cleaning_image',
            'drain_connection_image',
            'additional_image',
        ]);

        try {
            $data = $request->validated();

            // ── handle file uploads ───────────────────────────────────────────────
            foreach ($this->imageFields() as $field) {

                if ($field === 'additional_image') {
                    if ($request->hasFile('additional_image')) {
                        $paths = [];
                        foreach ($request->file('additional_image') as $file) {
                            if ($file->isValid()) {
                                $paths[] = $this->storeAttachment($file, $data['sales_order_id']);
                            }
                        }
                        // ✅ pass plain array — model cast handles json_encode
                        $data['additional_image'] = $paths ?: null;
                    }
                    continue;
                }

                if ($request->hasFile($field) && $request->file($field)->isValid()) {
                    $data[$field] = $this->storeAttachment($request->file($field), $data['sales_order_id']);
                }
            }

            // ── find appointment ──────────────────────────────────────────────────
            $appointment = DirectAppointment::where('sales_order_id', $data['sales_order_id'])
                ->where('book_id', $data['book_id'])
                ->latest('id')
                ->first();

            $data['appointment_id'] = $appointment->id ?? null;

            $created = $this->repo->create($data);

            // ── appointment not found ─────────────────────────────────────────────
            if (!$appointment) {
                $logService->failed(
                    techId: $techId,
                    action: 'store_complete_form',
                    bookId: $data['book_id'] ?? null,
                    salesOrderId: $data['sales_order_id'] ?? null,
                    message: 'Complete form stored but appointment not found',
                    requestPayload: $requestPayloadForLog,
                    responsePayload: [
                        'created_id' => $created->id,
                        'warning'    => 'Appointment not found, cannot store dy_attachment_body',
                    ],
                    userId: auth()->id(),
                );

                return (new CompleteFormResource($created))
                    ->additional(['warning' => 'Appointment not found, cannot store dy_attachment_body'])
                    ->response()
                    ->setStatusCode(201);
            }

            // ── build attachments list ────────────────────────────────────────────
            $attachments = [];

            foreach ($this->imageFields() as $field) {

                if ($field === 'additional_image') {
                    // ✅ already an array from model cast
                    foreach ((array) ($created->additional_image ?? []) as $path) {
                        $url = Storage::disk('s3')->url($path);
                        if (!empty($url)) {
                            $attachments[] = [
                                'URL'         => $url,
                                'Description' => 'additional_image',
                            ];
                        }
                    }
                    continue;
                }

                $path = $created->{$field} ?? null;
                if (!$path) {
                    continue;
                }

                $url = Storage::disk('s3')->url($path);
                if (!empty($url)) {
                    $attachments[] = [
                        'URL'         => $url,
                        'Description' => $field,
                    ];
                }
            }

            // ── build service note ────────────────────────────────────────────────
            $note = $data['notes'] ?? $data['service_name'] ?? $data['solution'] ?? null;

            $serviceNote = null;
            if (!empty($data['service_name']) || !empty($data['solution']) || !empty($data['problem'])) {
                $serviceNote = "Service Name: " . ($data['service_name'] ?? 'N/A') . "; " .
                    "Solution: "     . ($data['solution']     ?? 'N/A') . "; " .
                    "Problem: "      . ($data['problem']      ?? 'N/A');
            }

            // ── queue dynamics payload ────────────────────────────────────────────
            $this->dynamicsAttachmentPayloadService->queueAppointmentPayload(
                appointment: $appointment,
                note: $note,
                attachments: $attachments,
                resetResponse: true,
                serviceNote: $serviceNote,
            );

            // ── log success ───────────────────────────────────────────────────────
            $logService->success(
                techId: $techId,
                action: 'store_complete_form',
                bookId: $data['book_id'] ?? null,
                salesOrderId: $data['sales_order_id'] ?? null,
                message: 'Complete form stored successfully',
                requestPayload: $requestPayloadForLog,
                responsePayload: [
                    'created_id'     => $created->id,
                    'appointment_id' => $appointment->id ?? null,
                    'dy'             => [
                        'status'           => 'queued',
                        'attachments_urls' => $attachments,
                        'service_note'     => $serviceNote,
                    ],
                ],
                userId: auth()->id(),
                meta: [
                    'attachments_count' => count($attachments),
                ],
            );

            return (new CompleteFormResource($created))
                ->additional([
                    'dy' => [
                        'status'           => 'queued',
                        'attachments_urls' => $attachments,
                    ],
                ])
                ->response()
                ->setStatusCode(201);
        } catch (\Throwable $e) {
            $logService->failed(
                techId: $techId,
                action: 'store_complete_form',
                bookId: $request->input('book_id'),
                salesOrderId: $request->input('sales_order_id'),
                message: 'store complete form failed',
                requestPayload: $requestPayloadForLog,
                error: $e->getMessage(),
                userId: auth()->id(),
                meta: [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            );

            throw $e;
        }
    }

    public function show(int $id)
    {
        $record = $this->repo->findOrFail($id);
        return new CompleteFormResource($record);
    }

    public function update(UpdateCompleteFormRequest $request, int $id)
    {
        $record = $this->repo->findOrFail($id);
        $data = $request->validated();

        // ✅ if not provided, keep existing sales_order_id
        $salesOrderId = $data['sales_order_id'] ?? $record->sales_order_id;

        if (empty($salesOrderId)) {
            return response()->json([
                'success' => false,
                'message' => 'sales_order_id is required to store images'
            ], 422);
        }

        $imageFields = $this->imageFields();

        foreach ($imageFields as $field) {
            if ($request->hasFile($field) && $request->file($field)->isValid()) {

                // ✅ delete old file (optional but recommended)
                if (!empty($record->{$field})) {
                    try {
                        Storage::disk('s3')->delete($record->{$field});
                    } catch (\Throwable $e) {
                        Log::warning('Failed to delete old S3 file', [
                            'field' => $field,
                            'path' => $record->{$field},
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $data[$field] = $this->storeAttachment($request->file($field), $salesOrderId);
            }
        }

        // ensure sales_order_id stays consistent
        $data['sales_order_id'] = $salesOrderId;

        $updated = $this->repo->update($record, $data);

        return new CompleteFormResource($updated);
    }

    public function destroy(int $id): JsonResponse
    {
        $record = $this->repo->findOrFail($id);

        // ✅ delete related images from S3 before deleting DB record
        foreach ($this->imageFields() as $field) {
            if (!empty($record->{$field})) {
                try {
                    Storage::disk('s3')->delete($record->{$field});
                } catch (\Throwable $e) {
                    Log::warning('Failed to delete S3 file on destroy', [
                        'field' => $field,
                        'path' => $record->{$field},
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->repo->delete($record);

        return response()->json(['status' => true, 'message' => 'Deleted successfully']);
    }

    private function imageFields(): array
    {
        return [
            'home_salt_image',
            'device_salt_image',
            'carbon_depletion_image',
            'sink_cleaning_image',
            'drain_connection_image',
            'additional_image',
        ];
    }

    private function storeAttachment(UploadedFile $image, string $salesOrderId): string
    {
        $extension = $image->getClientOriginalExtension();
        $fileName  = uniqid('attachment_', true) . '.' . $extension;

        // ✅ keep your folder name
        $folder = "new_attachments/{$salesOrderId}";

        // ✅ IMPORTANT: no visibility/public ACL to avoid AccessControlListNotSupported
        return $image->storeAs($folder, $fileName, 's3');
    }




    public function resendAttachmentsToDynamicsByBookId(Request $request)
    {
        $logService = app(TechnicianAppointmentLogService::class);

        $validated = validator($request->all(), [
            'book_id' => 'required|string|max:255',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validated->errors(),
            ], 422);
        }

        $bookId = $request->input('book_id');
        $techId = auth()->user()?->tech_id;

        try {
            $appointment = DirectAppointment::where('book_id', $bookId)
                ->latest('id')
                ->first();

            if (!$appointment) {
                return response()->json([
                    'status' => false,
                    'message' => 'Appointment not found',
                ], 404);
            }

            $payload = $this->dynamicsAttachmentPayloadService->resendByAppointment($appointment);

            $logService->success(
                techId: $techId,
                action: 'resend_attachments_to_dynamics',
                bookId: $bookId,
                salesOrderId: $appointment->sales_order_id,
                message: 'Attachments re-queued successfully for Dynamics',
                requestPayload: ['book_id' => $bookId],
                responsePayload: [
                    'appointment_id' => $appointment->id,
                    'payload' => $payload,
                ],
                userId: auth()->id(),
            );

            return response()->json([
                'status' => true,
                'message' => 'Attachments resend queued successfully',
                'data' => [
                    'appointment_id' => $appointment->id,
                    'book_id' => $appointment->book_id,
                    'sales_order_id' => $appointment->sales_order_id,
                    'dy_attachment_status' => 'queued',
                    'payload' => $payload,
                ],
            ]);
        } catch (\Throwable $e) {
            $logService->failed(
                techId: $techId,
                action: 'resend_attachments_to_dynamics',
                bookId: $bookId,
                salesOrderId: null,
                message: 'Resend attachments to Dynamics failed',
                requestPayload: ['book_id' => $bookId],
                error: $e->getMessage(),
                userId: auth()->id(),
                meta: [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            );

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
