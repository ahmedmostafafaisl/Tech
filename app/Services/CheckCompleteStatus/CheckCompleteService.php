<?php

namespace App\Services\CheckCompleteStatus;

use App\Models\DirectAppointment;
use App\Models\DirectAppointmentPayment;
use App\Services\Payment\PaymentCompletionValidator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class CheckCompleteService
{


    public function checkPayments(string $book_id, array $body): array
    {
        /**
         * 🔴 Shared validation
         */
        $validation = PaymentCompletionValidator::validate($body);

        if (!$validation['ok']) {
            return $validation;
        }

        /**
         * 🟡 Required amount
         */
        $singleAppointment = app(\App\Http\Controllers\Api\NewDirectIntegrationController::class)
            ->refSingleAppointmentByBookId($book_id);

        if (
            !$singleAppointment ||
            !isset($singleAppointment['required_amount']) ||
            $singleAppointment['required_amount'] === 'not found'
        ) {
            return [
                'ok'     => false,
                'reason' => 'required_amount_not_found',
            ];
        }

        $requiredAmount = (float) $singleAppointment['required_amount'];

        /**
         * 🟢 Latest appointment
         */
        $directAppointment = DirectAppointment::where('book_id', $book_id)
            ->latest('id')
            ->first();

        if (!$directAppointment) {
            return [
                'ok'     => false,
                'reason' => 'appointment_not_found',
            ];
        }

        /**
         * 🟢 Total paid
         */
        $paid = (float) $directAppointment->payments()
            ->where('status', 'paid')
            ->sum('price');

        $discount = (float) ($directAppointment->discount ?? 0);
        $total    = $paid + $discount;

        if (abs($requiredAmount - $total) > 0.01) {
            return [
                'ok'       => false,
                'reason'   => 'amount_mismatch',
                'required' => $requiredAmount,
                'paid'     => $total,
            ];
        }

        return ['ok' => true];
    }


    public function authorizeTechOrFail($worker, $comment = null): void
    {

        $user = auth()->user();

        $tech_id = $user->tech_id ?? $user->technician_rec_id ?? null;
        // dd("Authorizing tech_id: $tech_id against worker: $worker");
        if (!$user || $tech_id != $worker) {
            // Log a comment before returning error
            $logMessage = $comment ?? "Unauthorized attempt by tech_id: " . ($tech_id ?? 'guest');
            Log::warning($logMessage, [
                'auth_user_id' => $user->id ?? null,
                'expected_tech_id' => $worker,
            ]);

            // Return 400 JSON error
            abort(response()->json([
                'status' => 'error',
                'message' => 'You must update the appointments'
            ], 403));
        }

        // Authorized → simply continue
    }

    public function authorizeTechOrFail_new($worker, $comment = null): void
    {
        $user = auth()->user();
        $tech_id = $user->tech_id ?? $user->technician_rec_id ?? null;

        if (!$user || (string)$tech_id !== (string)$worker) {
            $logMessage = $comment ?? "Unauthorized attempt by tech_id: " . ($tech_id ?? 'guest');

            Log::warning($logMessage, [
                'auth_user_id' => $user->id ?? null,
                'expected_tech_id' => $worker,
                'actual_tech_id' => $tech_id,
            ]);

            throw new HttpResponseException(
                response()->json([
                    'status' => false,
                    'message' => 'You must update the appointments',
                ], 403)
            );
        }
    }
    public function authorizeTechOrFail2($worker, $comment = null): void
    {
        $user = auth()->user();

        $techId = (string) ($user->tech_id ?? $user->technician_rec_id ?? '');
        $worker = (string) ($worker ?? '');

        if (!$user || $techId === '' || $techId !== $worker) {
            $logMessage = $comment ?? "Unauthorized attempt by tech_id: " . ($techId ?: 'guest');

            Log::warning($logMessage, [
                'auth_user_id'      => $user->id ?? null,
                'auth_tech_id'      => $techId,
                'expected_tech_id'  => $worker,
            ]);

            abort(response()->json([
                'status'  => 'error',
                'message' => 'You are not authorized to update this appointment',
            ], 403));
        }
    }


    public function checkDuplicatePayment($sales_order_id, $paymentType, $price, $referenceId)
    {
        // Normalize payment type to lowercase
        $paymentTypeLower = strtolower($paymentType);

        // Only check for these online payment types
        $onlineTypes = ['tabby', 'tabi', 'tamara'];

        if (!in_array($paymentTypeLower, $onlineTypes)) {
            return false; // Not relevant
        }

        // Normalize reference ID (remove # from start/end)
        $normalizedRef = trim($referenceId, '#');

        // Get existing payments with same sales_order_id, payment_type, and price
        $existingPayments = DirectAppointmentPayment::where('sales_order_id', $sales_order_id)
            ->whereRaw('LOWER(payment_type) = ?', [$paymentTypeLower])
            ->where('price', $price)
            ->get()
            ->filter(function ($payment) use ($normalizedRef) {
                $dbRef = trim($payment->reference_id, '#');
                return $dbRef === $normalizedRef;
            });

        if ($existingPayments->isNotEmpty()) {
            // Loop through the matched payments
            foreach ($existingPayments as $payment) {
                // Call TamaraService to get the status
                if ($paymentTypeLower === 'tamara') {
                    $allowedStatuses = ['approved', 'authorised', 'fully_captured'];

                    $response = app('App\Services\Payment\TamaraService')->getOrderStatus($payment->payment_id);
                    // Check if status indicates an active/approved payment
                    if (isset($response['status']) && in_array(strtolower($response['status']), $allowedStatuses)) {
                        $payment->status = 'paid';
                        $payment->save();
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Duplicate payment detected for this appointment'
                        ], 400);
                    }
                } else if ($paymentTypeLower === 'tabby' || $paymentTypeLower === 'tabi') {
                    $allowedStatuses = ['AUTHORIZED', 'CLOSED', 'CAPTURED'];

                    $response = app(\App\Services\Payment\TabbyService::class)
                        ->retrieveTabbyPayment($payment->payment_id);
                    // Check if status indicates an active/approved payment
                    if (is_array($response) && isset($response['status'])) {

                        $status = strtoupper($response['status']); // normalize

                        if (in_array($status, $allowedStatuses, true)) {
                            $payment->status = 'paid';
                            $payment->save();

                            return response()->json([
                                'status' => 'error',
                                'message' => 'Duplicate payment detected for this appointment'
                            ], 400);
                        }
                    }
                }
            }
        }

        // No active duplicate found → proceed
        return true;
    }
}
