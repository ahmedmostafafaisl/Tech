<?php

namespace App\Console\Commands;

use App\Models\CompleteIssue;
use Illuminate\Console\Command;
use App\Models\DirectAppointment;
use App\Services\DY365\DyService;
use Illuminate\Support\Facades\Log;
use App\Services\Payment\PaymentCompletionValidator;
use App\Http\Controllers\Api\NewDirectIntegrationController;

class CompleteSuccessPaymentsCommand extends Command
{
    // ✅ changed {body} -> {bodyB64}
    protected $signature = 'payments:complete {appointmentId} {bodyB64}';
    protected $description = 'Complete success payments for a specific appointment from Dynamics system';

    public function handle(): int
    {
        $appointmentId = (int) $this->argument('appointmentId');
        $bodyB64       = (string) $this->argument('bodyB64');

        $appointment = DirectAppointment::find($appointmentId);
        if (!$appointment) {
            $this->error("❌ Appointment with ID {$appointmentId} not found.");
            return Command::FAILURE;
        }

        // ✅ idempotency: already completed
        if ((int) ($appointment->complete_flag ?? 0) === 1) {
            $this->info("✅ Appointment {$appointmentId} already completed. Skipping.");
            return Command::SUCCESS;
        }



        Log::info("🚀 CompleteSuccessPaymentsCommand started", [
            'appointment_id' => $appointmentId,
        ]);

        // ✅ Decode Base64 -> JSON
        $bodyJson = base64_decode($bodyB64, true);
        if ($bodyJson === false) {
            return $this->fail(
                'Invalid base64 body provided',
                $appointment,
                $appointmentId
            );
        }

        // Decode JSON
        $body = json_decode($bodyJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->fail(
                'Invalid JSON body provided',
                $appointment,
                $appointmentId
            );
        }

        $validation = PaymentCompletionValidator::validate($body);
        if (!$validation['ok']) {
            return $this->saveIssueAndStop(
                $validation['reason'],
                $appointment,
                $appointmentId,
                $appointment->sales_order_id,
                $appointment->book_id ?? 'unknown',
                $body
            );
        }

        Log::info('Decoded body', ['body' => $body]);

        $contract = $body['_contract'] ?? null;
        if (!$contract) {
            return $this->fail('Missing _contract object', $appointment, $appointmentId, $body);
        }

        $salesOrderId = $contract['SalesOrderId'] ?? null;
        $bookId       = $contract['BookId'] ?? null;

        if (!$bookId) {
            return $this->fail('Missing BookId in contract', $appointment, $appointmentId, $body);
        }

        // ✅ Optional safety: ensure appointment matches payload
        if (!empty($appointment->book_id) && $appointment->book_id !== $bookId) {
            return $this->saveIssueAndStop(
                'BookId mismatch with appointment',
                $appointment,
                $appointmentId,
                $salesOrderId,
                $bookId,
                $body
            );
        }
        if (!empty($appointment->sales_order_id) && $salesOrderId && $appointment->sales_order_id !== $salesOrderId) {
            return $this->saveIssueAndStop(
                'SalesOrderId mismatch with appointment',
                $appointment,
                $appointmentId,
                $salesOrderId,
                $bookId,
                $body
            );
        }

        /**
         * ✅ Normalize SalesLines (accept both cases)
         */
        $salesLines = $contract['SalesLines']
            ?? $contract['salesLines']
            ?? null;

        /**
         * 🚫 HARD STOP — SalesLines validation
         */
        if (!is_array($salesLines) || empty($salesLines)) {
            return $this->saveIssueAndStop(
                'SalesLines is missing or empty',
                $appointment,
                $appointmentId,
                $salesOrderId,
                $bookId,
                $body
            );
        }

        try {
            /**
             * 🟡 Step 1: Get required amount
             */
            $controller = app(NewDirectIntegrationController::class);
            $singleAppointment = $controller->refSingleAppointmentByBookId($bookId);

            if ($singleAppointment instanceof \Illuminate\Http\JsonResponse) {
                $singleAppointment = $singleAppointment->getData(true);
            }

            $requiredAmount = $singleAppointment['required_amount'] ?? null;
            if ($requiredAmount === null) {
                return $this->saveIssueAndStop(
                    'Unable to retrieve required amount',
                    $appointment,
                    $appointmentId,
                    $salesOrderId,
                    $bookId,
                    $body
                );
            }

            $requiredAmount = (float) $requiredAmount;

            // ✅ Same shared calculator/flag as sendPaymentLinks/completeAppointment/
            // checkPaymentStatus/PaymentCompletionDispatcher/checkPayments() —
            // this was a SECOND, independent place (separate from
            // CheckCompleteService::checkPayments(), already fixed) that
            // still compared against the RAW DY365 value instead of the
            // correctly-calculated one, causing the exact same false
            // "Amount mismatch" for any appointment where PaidAmount/
            // used_balance actually reduced what's owed.
            if (\App\Models\Setting::isActive('new_required_amount_calculation_active')) {
                $paidAmount  = (float) ($singleAppointment['PaidAmount'] ?? 0);
                $usedBalance = (float) ($singleAppointment['used_balance'] ?? 0);

                $requiredAmount = app(\App\Services\Payment\RequiredAmountCalculator::class)
                    ->calculate($requiredAmount, $paidAmount, $usedBalance);
            }

            /**
             * 🟢 Step 2: Calculate amount
             */
            $totalAmount = (float) collect($salesLines)->sum(
                fn($line) => (float) ($line['TotalAmount'] ?? 0)
            );

            $discount = (float) ($contract['Discount'] ?? 0);

            // ⚠️ لو خصمك في البودي بييجي موجب (50) يبقى لازم تطرحه.
            // لكن في نظامك غالبًا الخصم هو قيمة بتتجمع مع المدفوعات عشان يوصل للـ required.
            // (PaidSum + Discount == Required) => calculated = total + discount
            $calculatedAmount = $totalAmount + $discount;

            /**
             * 🟠 Step 3: Amount mismatch
             */
            if (abs($calculatedAmount - $requiredAmount) > 0.01) {
                $collect = $requiredAmount - $calculatedAmount;

                CompleteIssue::create([
                    'appointment_id'    => $appointmentId,
                    'sales_order_id'    => $salesOrderId,
                    'book_id'           => $bookId,
                    'required_amount'   => $requiredAmount,
                    'calculated_amount' => $calculatedAmount,
                    'body'              => json_encode($body, JSON_UNESCAPED_UNICODE),
                    'error_message'     => 'Amount mismatch',
                ]);

                $appointment->update([
                    'status'  => 'pending',
                    'collect' => $collect,
                    'dy_response' => json_encode([
                        'error' => 'Amount mismatch',
                        'required_amount' => $requiredAmount,
                        'calculated_amount' => $calculatedAmount,
                        'collect' => $collect,
                    ], JSON_UNESCAPED_UNICODE),
                ]);

                $this->error("⚠️ Amount mismatch detected. Collect = {$collect}");
                return Command::FAILURE;
            }

            /**
             * ✅ Step 4: Complete payment
             */
            Log::info("📤 Sending completeSuccessPaymentsV2", [
                'appointment_id' => $appointmentId,
            ]);

            $appointment->update([
                'dy_body' => json_encode($body, JSON_UNESCAPED_UNICODE),
            ]);

            $response = app(DyService::class)->completeSuccessPaymentsV2($body);
            $data     = is_array($response) ? $response : (array) $response;

            if (($data['Status'] ?? false) === true) {
                $appointment->update([
                    'complete_flag'      => 1,
                    'dy_response'        => json_encode($data, JSON_UNESCAPED_UNICODE),
                    'complete_v2_calling' => "done:" . now()->format('Y-m-d H:i:s'),
                ]);

                $this->info("✅ Appointment {$appointmentId} completed successfully.");
                return Command::SUCCESS;
            }

            CompleteIssue::create([
                'appointment_id'    => $appointmentId,
                'sales_order_id'    => $salesOrderId,
                'book_id'           => $bookId,
                'required_amount'   => $requiredAmount,
                'calculated_amount' => $calculatedAmount,
                'body'              => json_encode($body, JSON_UNESCAPED_UNICODE),
                'error_message'     => 'DyService returned unsuccessful response',
            ]);

            $appointment->update([
                'dy_response' => json_encode($data, JSON_UNESCAPED_UNICODE),
                'complete_v2_calling' => "failed:" . now()->format('Y-m-d H:i:s'),
            ]);

            $this->warn("⚠️ Payment API returned unsuccessful response.");
            return Command::FAILURE;
        } catch (\Throwable $e) {
            Log::error("❌ Command failed", [
                'appointment_id' => $appointmentId,
                'error'          => $e->getMessage(),
            ]);

            $appointment->update([
                'dy_response' => json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE),
                'complete_v2_calling' => "exception:" . now()->format('Y-m-d H:i:s'),
            ]);

            $this->error("❌ {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    protected function saveIssueAndStop(
        string $message,
        DirectAppointment $appointment,
        int $appointmentId,
        ?string $salesOrderId,
        string $bookId,
        array $body
    ): int {
        Log::warning("⛔ CompleteSuccessPayments stopped", [
            'appointment_id' => $appointmentId,
            'sales_order_id' => $salesOrderId,
            'book_id'        => $bookId,
            'reason'         => $message,
        ]);

        CompleteIssue::create([
            'appointment_id'    => $appointmentId,
            'sales_order_id'    => $salesOrderId,
            'book_id'           => $bookId,
            'required_amount'   => null,
            'calculated_amount' => null,
            'body'              => json_encode($body, JSON_UNESCAPED_UNICODE),
            'error_message'     => $message,
        ]);

        $appointment->update([
            'status'      => 'pending',
            'dy_response' => json_encode([
                'error'  => $message,
                'reason' => 'forced_stop',
            ], JSON_UNESCAPED_UNICODE),
            'complete_v2_calling' => "stopped:" . now()->format('Y-m-d H:i:s'),
        ]);

        $this->error("❌ {$message}");

        return Command::FAILURE;
    }

    protected function fail(
        string $message,
        DirectAppointment $appointment,
        int $appointmentId,
        array $body = [],
        ?string $salesOrderId = null,
        ?string $bookId = null
    ): int {
        return $this->saveIssueAndStop(
            $message,
            $appointment,
            $appointmentId,
            $salesOrderId,
            $bookId ?? ($appointment->book_id ?? 'unknown'),
            $body
        );
    }
}
