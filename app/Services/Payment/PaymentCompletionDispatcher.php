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

        // ✅ New required_amount calculation (Step 4 — same shared
        // calculator/flag as sendPaymentLinks/completeAppointment/
        // checkPaymentStatus). PaidAmount/used_balance are only present
        // when $appointmentData came from the live DY365 response
        // (source === 'dynamics'); the DB-fallback branch has neither
        // key, so they safely default to 0 there — same as
        // required_amount's own existing fallback.
        if (\App\Models\Setting::isActive('new_required_amount_calculation_active')) {
            $paidAmount  = (float) ($appointmentData['PaidAmount'] ?? 0);
            $usedBalance = abs((float) ($appointmentData['used_balance'] ?? 0));

            $required = app(\App\Services\Payment\RequiredAmountCalculator::class)
                ->calculate($required, $paidAmount, $usedBalance);
        }

        $serialPayload = $this->buildSalesLinesSerial($appointmentData);

        // ✅ Read directly from the appointment record — the caller is
        // expected to have already saved installment_status onto
        // $appointment before calling dispatch(), so this always reflects
        // whatever was actually persisted, rather than requiring every
        // caller to also pass it separately as an extra argument.
        $installmentStatus = $appointment->installment_status;

        // Same InstallmentStatus/fes-tech-visit/fes-transportation rules as
        // SendPaymentLinksRequest / NewCompleteAppointmentRequest, checked
        // here against the appointment's real sales lines (not payment
        // lines) instead of client-submitted items.
        $validation = $this->validateInstallmentStatusItems($appointment, $appointmentData);
        if ($validation !== null) {
            return $validation;
        }

        // ✅ Stock validation — was previously ONLY enforced upstream in
        // sendPaymentLinks()/completeAppointment() (via
        // salesLinesSummaryByBookId()), meaning any other caller of
        // dispatch()/preCheck() (a retry job, an admin action, etc.) had
        // zero stock enforcement of its own. Now checked here directly,
        // so this service is self-contained rather than trusting callers
        // to have already validated it.
        $stockValidation = $this->validateStockAvailability($appointment, $appointmentData);
        if ($stockValidation !== null) {
            return $stockValidation;
        }

        // ✅ naqi-s00004 exclusivity — unconditional, applies regardless
        // of order_type or InstallmentStatus.
        $itemExclusivityValidation = $this->validateItemExclusivity($appointmentData);
        if ($itemExclusivityValidation !== null) {
            return $itemExclusivityValidation;
        }

        // ✅ Cooldown — same rule as sendPaymentLinks()/completeAppointment():
        // don't proceed until 15 minutes have passed since the
        // technician's previous appointment, unless the most recent one
        // IS this same appointment.
        $cooldownRemaining = app(NewDirectIntegrationController::class)
            ->getRemainingCooldownMinutes($appointment->tech_id, $appointment->book_id);

        if (!(is_null($cooldownRemaining) || $cooldownRemaining === 0)) {
            return [
                'ok'     => false,
                'reason' => 'cooldown_active',
                'detail' => 'Please wait before sending another appointment to Dynamics.',
                'timer'  => $cooldownRemaining,
            ];
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
        //
        // PaidAmount / UsedBalance here are informational payload fields
        // for DY365 — computed fresh from $appointmentData regardless of
        // whether the new_required_amount_calculation_active flag is on,
        // since they're just reporting what happened, not changing
        // $required itself. UsedBalance specifically reflects the amount
        // ACTUALLY consumed by the calculation (0 if paidAmount alone
        // already covered everything), not the raw available balance
        // DY365 reported — that's why calculateUsedBalanceApplied() is
        // used here instead of just passing used_balance straight through.
        $rawRequiredAmount = (float) ($appointmentData['required_amount'] ?? 0);
        $paidAmountForBody = (float) ($appointmentData['PaidAmount'] ?? 0);
        $usedBalanceApplied = app(RequiredAmountCalculator::class)->calculateUsedBalanceApplied(
            $rawRequiredAmount,
            $paidAmountForBody,
            abs((float) ($appointmentData['used_balance'] ?? 0))
        );

        $body = [
            '_contract' => array_merge([
                'worker'            => $appointment->tech_id ?? null,
                'SalesOrderId'      => $appointment->sales_order_id,
                'BookId'            => $appointment->book_id,
                'Discount'          => (float) ($appointment->discount ?? 0),
                'PaidAmount'        => $paidAmountForBody,
                'UsedBalance'       => $usedBalanceApplied ?? 0,
                'SalesLines'        => $salesLines,
                // ⚠ FIXED (again — this reverted back to the typo in a
                // fresh clone since the earlier fix only ever existed as
                // a file, never actually landed in the real repo):
                'InstalltionStatus' => $installmentStatus,
            ], $serialPayload),
        ];

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
     * Same InstallmentStatus/fes-tech-visit/fes-transportation rule set as
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
        $orderType = $appointmentData['OrderTypeId'] ?? $appointment->order_type;

        // Widened from تركيب-only: the delivery-fee/TotalAmountSum rule
        // below also applies to منتجات, so both types need to reach this
        // method now. The InstallmentStatus-specific sub-checks further
        // below remain scoped to تركيب only, since InstallmentStatus is
        // only ever populated for that order type.
        if (!in_array($orderType, ['تركيب', 'منتجات'], true)) {
            return null;
        }

        $salesLines = $appointmentData['sales_lines'] ?? [];

        $hasFesTechVisit = collect($salesLines)->contains(
            fn($line) => strtolower(trim($line['ItemNumber'] ?? '')) === 'fes-tech-visit'
        );
        $hasDlvFee1 = collect($salesLines)->contains(
            fn($line) => strtolower(trim($line['ItemNumber'] ?? '')) === 'fes-transportation'
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
                'detail' => 'ItemNumber = fes-tech-visit and ItemNumber = fes-transportation cannot both be present on the same appointment.',
            ];
        }

        // Same TotalAmountSum/fes-transportation rule as SendPaymentLinksRequest /
        // NewCompleteAppointmentRequest's controller-level check:
        //   - TotalAmountSum < 500 (and not tech-visit-only) → sales_lines
        //     MUST include fes-transportation.
        //   - TotalAmountSum >= 500 → sales_lines must NOT include fes-transportation.
        $totalAmountSum = (float) ($appointmentData['TotalAmountSum'] ?? 0);
        $logService = app(\App\Services\Logs\TechnicianAppointmentLogService::class);

        if ($totalAmountSum < 500) {
            if (!$hasFesTechVisit && !$hasDlvFee1) {
                $logService->validationFailed(
                    techId: $appointment->tech_id,
                    action: 'payment_completion_dispatch',
                    bookId: $appointment->book_id,
                    salesOrderId: $appointment->sales_order_id,
                    message: 'Missing required delivery fee line for low-value تركيب/منتجات appointment',
                    responsePayload: [
                        'order_type_id'    => $orderType,
                        'total_amount_sum' => $totalAmountSum,
                    ],
                );

                return [
                    'ok'     => false,
                    'reason' => 'total_sum_validation_lower_than_500_missing_delivery_fee',
                    'detail' => 'total_sum_validation_lower_than_500_missing_delivery_fee(fes-transportation)',
                ];
            }
        } else {
            if ($hasDlvFee1) {
                $logService->validationFailed(
                    techId: $appointment->tech_id,
                    action: 'payment_completion_dispatch',
                    bookId: $appointment->book_id,
                    salesOrderId: $appointment->sales_order_id,
                    message: 'Unexpected delivery fee line for a تركيب/منتجات appointment that does not qualify for it',
                    responsePayload: [
                        'order_type_id'    => $orderType,
                        'total_amount_sum' => $totalAmountSum,
                    ],
                );

                return [
                    'ok'     => false,
                    'reason' => 'total_sum_validation_500_or_more_unexpected_delivery_fee',
                    'detail' => 'total_sum_validation_500_or_more_unexpected_delivery_fee(fes-transportation)',
                ];
            }
        }

        return null;
    }

    /**
     * Stock validation — same logic as
     * NewDirectIntegrationController::salesLinesSummaryByBookId(), reused
     * via buildStockMapForSalesLines() rather than duplicated, but sourced
     * from the technician's own record (getTechnicianUser($appointment->tech_id))
     * instead of Auth::user(), since this service can run outside an
     * authenticated HTTP request (a queued job, an admin action, etc.).
     *
     * Filters out service-type lines (OrderTypeId === 'خدمات') the same
     * way the controller version does, then flags any remaining line
     * where the technician's warehouse has zero or insufficient quantity
     * for what the appointment requires.
     *
     * Returns null when valid, or ['ok' => false, ...] to return
     * immediately when stock is insufficient — same convention as
     * validateInstallmentStatusItems().
     */
    protected function validateStockAvailability(DirectAppointment $appointment, array $appointmentData): ?array
    {
        $salesLines = $appointmentData['sales_lines'] ?? [];

        if (empty($salesLines)) {
            // Nothing to check — the free-appointment / db_fallback paths
            // already handle an empty sales_lines case elsewhere.
            return null;
        }

        $technicianUser = app(NewDirectIntegrationController::class)
            ->getTechnicianUser((string) $appointment->tech_id);

        if (!$technicianUser || empty($technicianUser->warehouse_id)) {
            Log::warning('Stock validation skipped — technician or warehouse_id not found.', [
                'appointment_id' => $appointment->id,
                'tech_id'        => $appointment->tech_id,
            ]);

            return null; // can't validate without a warehouse — don't block on missing data
        }

        $nonServiceLines = array_values(array_filter($salesLines, function ($line) {
            return ($line['OrderTypeId'] ?? null) !== 'خدمات';
        }));

        if (empty($nonServiceLines)) {
            return null; // all lines are services — nothing physical to check stock for
        }

        $stockMap = app(NewDirectIntegrationController::class)
            ->buildStockMapForSalesLines($nonServiceLines, $technicianUser->warehouse_id);

        $zeroStockLines = array_values(array_filter(
            array_map(function ($line) use ($stockMap) {
                $itemNumber   = strtolower($line['ItemNumber'] ?? '');
                $maxQuantity  = $stockMap->get($itemNumber)['Quantity'] ?? 0;
                $lineQuantity = $line['Quantity'] ?? 0;

                return [
                    'item_number'  => $line['ItemNumber'] ?? null,
                    'quantity'     => $lineQuantity,
                    'max_quantity' => $maxQuantity,
                ];
            }, $nonServiceLines),
            fn($line) => $line['max_quantity'] === 0 || $line['max_quantity'] < $line['quantity']
        ));

        if (!empty($zeroStockLines)) {
            return [
                'ok'     => false,
                'reason' => 'insufficient_stock',
                'detail' => $zeroStockLines,
            ];
        }

        return null;
    }

    /**
     * naqi-s00004 must be kept entirely separate — if present, it cannot
     * be combined with any other products, regardless of order_type or
     * InstallmentStatus. Unconditional, unlike validateInstallmentStatusItems()
     * which is gated to تركيب/منتجات only.
     */
    protected function validateItemExclusivity(array $appointmentData): ?array
    {
        $salesLines = collect($appointmentData['sales_lines'] ?? []);

        $hasNaqiS00004 = $salesLines->contains(
            fn($line) => strtolower(trim($line['ItemNumber'] ?? '')) === 'naqi-s00004'
        );

        if ($hasNaqiS00004 && $salesLines->count() !== 1) {
            return [
                'ok'     => false,
                'reason' => 'item_exclusivity_violation',
                'detail' => 'item_exclusivity_validation(naqi-s00004)',
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

        // ✅ Same new calculation as dispatch() — kept in sync since this
        // method exists specifically to predict dispatch()'s outcome.
        if (\App\Models\Setting::isActive('new_required_amount_calculation_active')) {
            $paidAmount  = (float) ($appointmentData['PaidAmount'] ?? 0);
            $usedBalance = abs((float) ($appointmentData['used_balance'] ?? 0));

            $required = app(\App\Services\Payment\RequiredAmountCalculator::class)
                ->calculate($required, $paidAmount, $usedBalance);
        }

        $serialPayload   = $this->buildSalesLinesSerial($appointmentData);

        // ✅ Read directly from the appointment record, same as dispatch().
        $installmentStatus = $appointment->installment_status;

        // Same InstallmentStatus/fes-tech-visit/fes-transportation rules as
        // dispatch() — kept in sync via the shared helper since this
        // method exists specifically to predict dispatch()'s outcome
        // before allowing form submission.
        $validation = $this->validateInstallmentStatusItems($appointment, $appointmentData);
        if ($validation !== null) {
            return $validation;
        }

        // Same stock check as dispatch() — kept in sync via the shared helper.
        $stockValidation = $this->validateStockAvailability($appointment, $appointmentData);
        if ($stockValidation !== null) {
            return $stockValidation;
        }

        // Same item-exclusivity check as dispatch().
        $itemExclusivityValidation = $this->validateItemExclusivity($appointmentData);
        if ($itemExclusivityValidation !== null) {
            return $itemExclusivityValidation;
        }

        // Same cooldown check as dispatch() — kept in sync since this
        // method exists specifically to predict dispatch()'s outcome.
        $cooldownRemaining = app(NewDirectIntegrationController::class)
            ->getRemainingCooldownMinutes($appointment->tech_id, $appointment->book_id);

        if (!(is_null($cooldownRemaining) || $cooldownRemaining === 0)) {
            return [
                'ok'     => false,
                'reason' => 'cooldown_active',
                'detail' => 'Please wait before sending another appointment to Dynamics.',
                'timer'  => $cooldownRemaining,
            ];
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
                // 'UsedBalance'       => (float) ($appointment->used_balance ?? 0),
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
