<?php

namespace App\Services\Payment;

use App\Http\Controllers\Api\Evaluation\EvaluationMessageController;
use App\Http\Controllers\Api\NewDirectIntegrationController;
use App\Models\AppointmentTransactionSerial;
use App\Models\DirectAppointment;
use App\Services\CheckCompleteStatus\CheckCompleteService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PaymentCompletionDispatcher
{
    public function __construct(
        protected CheckCompleteService $checkCompleteService
    ) {}

    public function dispatch(DirectAppointment $appointment, array $extraBody = [])
    {
        // 1) idempotency
        if ((int) ($appointment->complete_flag ?? 0) === 1) {
            return ['ok' => true, 'skipped' => true, 'reason' => 'already_completed'];
        }
        $appointmentData = $this->getAppointmentDataFromDynamics($appointment);
        $required = (float) ($appointmentData['required_amount'] ?? 0);
        $serialPayload = $this->buildSalesLinesSerial($appointmentData);

        // ✅ Read directly from the appointment record — the caller is
        // expected to have already saved installment_status onto
        // $appointment before calling dispatch(), so this always reflects
        // whatever was actually persisted, rather than requiring every
        // caller to also pass it separately as an extra argument.
        $installmentStatus = $appointment->installment_status;

        // Same InstallmentStatus/fes-tech-visit/dlv-fee1 rules as
        // SendPaymentLinksRequest / NewCompleteAppointmentRequest, checked
        // here against the appointment's real sales lines (not payment
        // lines) instead of client-submitted items.
        $validation = $this->validateInstallmentStatusItems($appointment, $appointmentData);
        if ($validation !== null) {
            return $validation;
        }

        // 2) lock marker (with stale running unlock)
        $marker = (string) ($appointment->complete_v2_calling ?? '');

        // ✅ لو required=0 وعايز تعيد الإرسال: reset markers تلقائيًا
        // (ده يحقق طلبك "زي completeAppointment")
        if (abs($required) < 0.01) {
            if (
                str_contains($marker, 'running:')
                || str_contains($marker, 'blocked:')
                || str_contains($marker, 'failed:')
                || str_contains($marker, 'exception:')
                || str_contains($marker, 'stopped:')
            ) {
                $appointment->update([
                    'complete_v2_calling' => null,
                    'complete_flag'       => 0,
                ]);
                $marker = '';
            }
        } else {
            // نفس منطق stale running للـ paid
            if (str_starts_with($marker, 'running:')) {
                $ts = trim(str_replace('running:', '', $marker));
                try {
                    $started = Carbon::parse($ts);
                    if ($started->diffInMinutes(now()) > 1) {
                        Log::warning('Stale running lock detected. Allowing retry.', [
                            'appointment_id' => $appointment->id,
                            'marker' => $marker,
                        ]);
                        $marker = '';
                    }
                } catch (\Throwable $e) {
                    $marker = '';
                }
            }
        }

        $alreadyTriggered =
            str_contains($marker, 'running:')
            || str_contains($marker, 'done:')
            || str_contains($marker, 'failed:')
            || str_contains($marker, 'exception:')
            || str_contains($marker, 'blocked:')
            || str_contains($marker, 'stopped:');

        if ($alreadyTriggered) {
            return ['ok' => true, 'skipped' => true, 'reason' => 'already_triggered', 'marker' => $marker];
        }

        // 3) build salesLines
        $payments = $appointment->payments()
            ->where('status', 'paid')
            ->orderBy('id')
            ->get()
            ->unique(fn($p) => ($p->payment_type ?? 'unknown') . '_' . ($p->reference_id ?? $p->id))
            ->values();

        if ($payments->isEmpty()) {
            if (abs($required) < 0.01) {
                // ✅ required=0 => allow with dummy salesLines
                $salesLines = [
                    [
                        'TotalAmount'      => 0.0,   // لو validator بيرفض 0 خليها 0.01
                        'PaymentReference' => null,
                        'PaymentMethod'    => 'CASH',
                    ],
                ];
            } else {
                return ['ok' => false, 'reason' => 'no_paid_payments'];
            }
        } else {
            $salesLines = $payments->map(function ($p) {
                $method = strtoupper((string) ($p->payment_type ?? ''));

                if ($method === 'TABBY') $method = 'TABI';
                if ($method === 'TABI')  $method = 'TABI';
                if ($method === 'TAMARA') $method = 'TAMARA';
                if ($method === 'E-COMMERCE' || $method === 'ECOMMERCE') $method = 'E-Commerce';

                return [
                    'TotalAmount'      => (float) ($p->price ?? 0),
                    'PaymentReference' => $p->payment_id ?: ($p->reference_id ?: null),
                    'PaymentMethod'    => $method,
                ];
            })->toArray();
        }

        // 4) build body
        $body = [
            '_contract' => array_merge([
                'worker'            => $appointment->tech_id ?? null,
                'SalesOrderId'      => $appointment->sales_order_id,
                'BookId'            => $appointment->book_id,
                'Discount'          => (float) ($appointment->discount ?? 0),
                'UsedBalance'       => ($appointmentData['used_balance'] ?? null),
                'SalesLines'        => $salesLines,
                // ⚠ FIXED: was 'InstalltionStatus' (typo) — dispatch()
                // and preCheck() previously disagreed on this key name.
                'InstalltionStatus' => $installmentStatus,
            ], $serialPayload),
        ];
        // dd($appointment, $body);
        if (!empty($extraBody)) {
            $body = array_replace_recursive($body, $extraBody);
            $body['_contract']['Discount'] = (float) ($appointment->discount ?? 0);
        }



        // 5) pre-check
        $check = $this->checkCompleteService->checkPayments($appointment->book_id, $body);

        // ✅ لو السبب sales_lines_empty ومع required=0: اعمل fallback lines وحاول تاني
        if (!(($check['ok'] ?? false) === true)) {
            if (abs($required) < 0.01 && ($check['reason'] ?? null) === 'sales_lines_empty') {
                $body['_contract']['salesLines'] = [
                    [
                        'TotalAmount'      => 0.0,
                        'PaymentReference' => null,
                        'PaymentMethod'    => 'CASH',
                    ],
                ];
                $check2 = $this->checkCompleteService->checkPayments($appointment->book_id, $body);
                if (($check2['ok'] ?? false) !== true) {
                    $appointment->update([
                        'complete_v2_calling' => 'blocked:' . now()->format('Y-m-d H:i:s'),
                    ]);
                    return ['ok' => false, 'reason' => $check2['reason'] ?? 'blocked', 'check' => $check2];
                }
            } else {
                $appointment->update([
                    'complete_v2_calling' => 'blocked:' . now()->format('Y-m-d H:i:s'),
                ]);
                return ['ok' => false, 'reason' => $check['reason'] ?? 'blocked', 'check' => $check];
            }
        }

        // 6) exec
        $appointment->update([
            'complete_v2_calling' => 'running:' . now()->format('Y-m-d H:i:s'),
        ]);

        $payloadJson = json_encode($body, JSON_UNESCAPED_UNICODE);
        $payloadB64  = base64_encode($payloadJson);

        $bgLogFile = storage_path('logs/payments_complete_bg.log');

        $cmd = "php " . escapeshellarg(base_path('artisan')) . " payments:complete "
            . escapeshellarg((string) $appointment->id) . " "
            . escapeshellarg($payloadB64)
            . " >> " . escapeshellarg($bgLogFile) . " 2>&1 &";

        Log::info('Dispatch payments:complete exec', [
            'appointment_id' => $appointment->id,
            'cmd'            => $cmd,
            'bg_log'         => $bgLogFile,
        ]);

        exec($cmd);

        // 7) send WhatsApp evaluation if required
        //check if customer phone and order type are available before sending evaluation
        if (empty($appointment->customer_phone) || empty($appointment->order_type)) {
            Log::warning('Skipping evaluation message dispatch due to missing phone or order type', [
                'appointment_id' => $appointment->id,
                'customer_phone' => $appointment->customer_phone,
                'order_type'     => $appointment->order_type,
            ]);
            return ['ok' => true, 'dispatched' => true, 'evaluation_skipped' => true, 'reason' => 'missing_phone_or_order_type'];
        }
        app(EvaluationMessageController::class)->createAndSendEvaluation(
            $appointment->customer_phone,
            $appointment->order_type,
            $appointment->book_id
        );

        return ['ok' => true, 'dispatched' => true];
    }

    protected function getAppointmentDataFromDynamics(DirectAppointment $appointment): array
    {
        try {
            $controller = app(NewDirectIntegrationController::class);
            $singleAppointment = $controller->refSingleAppointmentByBookId($appointment->book_id);

            if ($singleAppointment instanceof \Illuminate\Http\JsonResponse) {
                $singleAppointment = $singleAppointment->getData(true);
            }

            if (
                empty($singleAppointment) ||
                !is_array($singleAppointment)
            ) {


                return [
                    'book_id'         => $appointment->book_id,
                    'sales_order_id'  => $appointment->sales_order_id,
                    'required_amount' => (float) ($appointment->required_amount ?? 0),
                    'sales_lines'     => [],
                    'source'          => 'db_fallback',
                ];
            }

            return $singleAppointment + [
                'source' => 'dynamics',
            ];
        } catch (\Throwable $e) {


            return [
                'book_id'         => $appointment->book_id,
                'sales_order_id'  => $appointment->sales_order_id,
                'required_amount' => (float) ($appointment->required_amount ?? 0),

                'sales_lines'     => [],
                'source'          => 'db_fallback',
            ];
        }
    }

    /**
     * Same InstallmentStatus/fes-tech-visit/dlv-fee1 rule set as
     * SendPaymentLinksRequest / NewCompleteAppointmentRequest, checked
     * against the appointment's real sales_lines. Shared between
     * dispatch() and preCheck() so they can never drift apart.
     *
     * Returns null when valid, or the ['ok' => false, ...] array to
     * return immediately when invalid.
     */
    protected function validateInstallmentStatusItems(DirectAppointment $appointment, array $appointmentData): ?array
    {
        $installmentStatus = $appointment->installment_status;

        if ($appointment->order_type !== 'تركيب') {
            return null;
        }

        $salesLines = $appointmentData['sales_lines'] ?? [];

        $hasFesTechVisit = collect($salesLines)->contains(
            fn($line) => strtolower(trim($line['ItemNumber'] ?? '')) === 'fes-tech-visit'
        );
        $hasDlvFee1 = collect($salesLines)->contains(
            fn($line) => strtolower(trim($line['ItemNumber'] ?? '')) === 'dlv-fee1'
        );

        if ($installmentStatus === 'Need_installation') {
            $isValidSingleVisitLine =
                count($salesLines) === 1 &&
                strtolower(trim($salesLines[0]['ItemNumber'] ?? '')) === 'fes-tech-visit';

            if (!$isValidSingleVisitLine) {
                return [
                    'ok'     => false,
                    'reason' => 'installment_status_item_mismatch',
                    'detail' => 'When InstallmentStatus is Need_installation, sales lines must contain exactly one item with ItemNumber = fes-tech-visit, and it cannot be combined with any other products.',
                ];
            }
        } elseif ($installmentStatus === 'Completed') {
            if ($hasFesTechVisit) {
                return [
                    'ok'     => false,
                    'reason' => 'installment_status_item_mismatch',
                    'detail' => 'When InstallmentStatus is Completed, sales lines must not contain ItemNumber = fes-tech-visit.',
                ];
            }
        }

        if ($hasFesTechVisit && $hasDlvFee1) {
            return [
                'ok'     => false,
                'reason' => 'fes_tech_visit_dlv_fee1_conflict',
                'detail' => 'ItemNumber = fes-tech-visit and ItemNumber = dlv-fee1 cannot both be present on the same appointment.',
            ];
        }

        return null;
    }

    /**
     * Validates whether the appointment is eligible for completion dispatch.
     * Runs the same idempotency + lock + pre-check logic as dispatch()
     * but WITHOUT writing any markers or firing the command.
     * Used as a gate before allowing form submission.
     */
    public function preCheck(DirectAppointment $appointment): array
    {
        // ── Already completed ─────────────────────────────────────────────
        if ((int) ($appointment->complete_flag ?? 0) === 1) {
            return ['ok' => true, 'skipped' => true, 'reason' => 'already_completed'];
        }

        // ── Load Dynamics data + required amount ──────────────────────────
        $appointmentData = $this->getAppointmentDataFromDynamics($appointment);
        $required        = (float) ($appointmentData['required_amount'] ?? 0);
        $serialPayload   = $this->buildSalesLinesSerial($appointmentData);

        // ✅ Read directly from the appointment record, same as dispatch().
        $installmentStatus = $appointment->installment_status;

        // Same InstallmentStatus/fes-tech-visit/dlv-fee1 rules as
        // dispatch() — kept in sync via the shared helper since this
        // method exists specifically to predict dispatch()'s outcome
        // before allowing form submission.
        $validation = $this->validateInstallmentStatusItems($appointment, $appointmentData);
        if ($validation !== null) {
            return $validation;
        }

        // ── Check lock marker ─────────────────────────────────────────────
        $marker = (string) ($appointment->complete_v2_calling ?? '');

        if (abs($required) < 0.01) {
            if (
                str_contains($marker, 'running:')
                || str_contains($marker, 'blocked:')
                || str_contains($marker, 'failed:')
                || str_contains($marker, 'exception:')
                || str_contains($marker, 'stopped:')
            ) {
                $marker = ''; // would be reset on actual dispatch
            }
        } else {
            if (str_starts_with($marker, 'running:')) {
                $ts = trim(str_replace('running:', '', $marker));
                try {
                    $started = Carbon::parse($ts);
                    if ($started->diffInMinutes(now()) > 1) {
                        $marker = '';
                    }
                } catch (\Throwable) {
                    $marker = '';
                }
            }
        }

        $alreadyTriggered =
            str_contains($marker, 'running:')
            || str_contains($marker, 'done:')
            || str_contains($marker, 'failed:')
            || str_contains($marker, 'exception:')
            || str_contains($marker, 'blocked:')
            || str_contains($marker, 'stopped:');

        if ($alreadyTriggered) {
            return ['ok' => true, 'skipped' => true, 'reason' => 'already_triggered', 'marker' => $marker];
        }

        // ── Build payments + salesLines ───────────────────────────────────
        $payments = $appointment->payments()
            ->where('status', 'paid')
            ->orderBy('id')
            ->get()
            ->unique('reference_id')
            ->values();

        if ($payments->isEmpty()) {
            if (abs($required) < 0.01) {
                $salesLines = [[
                    'TotalAmount'      => 0.0,
                    'PaymentReference' => null,
                    'PaymentMethod'    => 'CASH',
                ]];
            } else {
                return ['ok' => false, 'reason' => 'no_paid_payments'];
            }
        } else {
            $salesLines = $payments->map(function ($p) {
                $method = strtoupper((string) ($p->payment_type ?? ''));

                if ($method === 'TABBY')                              $method = 'TABI';
                if ($method === 'TAMARA')                             $method = 'TAMARA';
                if ($method === 'E-COMMERCE' || $method === 'ECOMMERCE') $method = 'E-Commerce';

                return [
                    'TotalAmount'      => (float) ($p->price ?? 0),
                    'PaymentReference' => $p->payment_id ?: ($p->reference_id ?: null),
                    'PaymentMethod'    => $method,
                ];
            })->toArray();
        }

        // ── Build body ────────────────────────────────────────────────────
        $body = [
            '_contract' => array_merge([
                'worker'            => $appointment->tech_id ?? null,
                'SalesOrderId'      => $appointment->sales_order_id,
                'BookId'            => $appointment->book_id,
                'Discount'          => (float) ($appointment->discount ?? 0),
                'SalesLines'        => $salesLines,
                'InstalltionStatus' => $installmentStatus,
            ], $serialPayload),
        ];

        // ── Pre-check only — no markers written, no command fired ─────────
        $check = $this->checkCompleteService->checkPayments($appointment->book_id, $body);

        if (($check['ok'] ?? false) !== true) {
            if (abs($required) < 0.01 && ($check['reason'] ?? null) === 'sales_lines_empty') {
                $body['_contract']['salesLines'] = [[
                    'TotalAmount'      => 0.0,
                    'PaymentReference' => null,
                    'PaymentMethod'    => 'CASH',
                ]];
                $check2 = $this->checkCompleteService->checkPayments($appointment->book_id, $body);

                if (($check2['ok'] ?? false) !== true) {
                    return ['ok' => false, 'reason' => $check2['reason'] ?? 'blocked', 'check' => $check2];
                }
            } else {
                return ['ok' => false, 'reason' => $check['reason'] ?? 'blocked', 'check' => $check];
            }
        }

        return ['ok' => true, 'can_dispatch' => true];
    }
    public function buildSalesLinesSerial(array $appointmentData): array
    {

        $salesLinesSerial = collect($appointmentData['sales_lines'] ?? [])
            ->map(function ($line) {
                $saleslineId = $line['SaleslineId'] ?? null;
                $isSerial    = (bool) ($line['IsSerial'] ?? false);
                $quantity    = (int) ($line['Quantity'] ?? 0);

                if (!$saleslineId) {
                    throw new \RuntimeException('SaleslineId is missing in sales line.');
                }

                if (!$isSerial) {
                    return null;
                }

                $serialNumbers = $this->getSerialNumbersForSalesLineFromDb($saleslineId);

                if (count($serialNumbers) !== $quantity) {
                    throw new \RuntimeException(
                        "SaleslineId {$saleslineId} requires {$quantity} serial numbers, got " . count($serialNumbers)
                    );
                }

                return [
                    'SaleslineId'  => $saleslineId,
                    'SerialNumber' => collect($serialNumbers)->map(fn($serial) => [
                        'Serial' => $serial,
                    ])->toArray(),
                ];
            })
            ->filter() // removes null (non-serial lines)
            ->values()
            ->toArray();

        return [
            'SalesLinesSerial' => $salesLinesSerial,
        ];
    }

    protected function getSerialNumbersForSalesLineFromDb(int|string $saleslineId): array
    {
        return AppointmentTransactionSerial::query()
            ->where('sales_line_rec_id', $saleslineId)
            ->whereNotNull('serial')
            ->pluck('serial')
            ->filter(fn($serial) => !blank($serial))
            ->values()
            ->toArray();
    }
}
