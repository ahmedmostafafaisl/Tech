<?php

namespace App\Services\Payment;

use App\Models\DirectAppointment;
use App\Models\DirectAppointmentPayment;
use Illuminate\Support\Facades\Log;

/**
 * Reconciles a DirectAppointmentPayment against Tamara's actual, live
 * order status — mirrors TabbyPaymentSyncService's role for Tabby.
 *
 * Never marks a payment paid just because a caller asked for it — always
 * re-verifies with Tamara's getOrderStatus() first. This is the same
 * verification logic already fixed inline in TamaraPaymentController's
 * newSuccess()/newNotification(), extracted here so all callers share
 * one implementation instead of copies that can drift apart.
 */
class TamaraPaymentSyncService
{
    public function __construct(
        private readonly TamaraService $tamara,
    ) {
    }

    /**
     * @return array{
     *     previous_local_status: string,
     *     tamara_status: string|null,
     *     new_local_status: string,
     *     changed: bool,
     *     verified_captured: bool,
     * }
     */
    public function syncPayment(DirectAppointmentPayment $payment): array
    {
        $previousStatus = $payment->status;

        if (!$payment->payment_id) {
            throw new \RuntimeException('Tamara order ID is missing on this payment record.');
        }

        $orderId = $payment->payment_id;

        $statusResponse = $this->tamara->getOrderStatus($orderId);

        if (isset($statusResponse['error']) && $statusResponse['error'] === true) {
            throw new \RuntimeException('Failed to retrieve order from Tamara: ' . ($statusResponse['message'] ?? 'Unknown error'));
        }

        $tamaraStatus = $statusResponse['status'] ?? null;

        Log::info('Syncing Tamara payment status.', [
            'order_id'        => $orderId,
            'reference_id'    => $payment->reference_id,
            'previous_status' => $previousStatus,
            'tamara_status'   => $tamaraStatus,
        ]);

        if ($tamaraStatus === 'approved') {
            $authResponse = $this->tamara->authorizeOrder($orderId);
            if (($authResponse['status'] ?? null) === 'authorised') {
                $this->tamara->captureOrderNew($payment->reference_id, $orderId);
            }
        } elseif ($tamaraStatus === 'authorised') {
            $this->tamara->captureOrderNew($payment->reference_id, $orderId);
        }

        // Re-check AFTER attempting authorize/capture — the actual proof.
        // 'captured'/'fully_captured' are inferred from Tamara's own
        // terminology, not confirmed against their docs.
        $finalStatusResponse = $this->tamara->getOrderStatus($orderId);
        $finalStatus = $finalStatusResponse['status'] ?? $tamaraStatus;

        $verifiedCaptured = in_array($finalStatus, ['captured', 'fully_captured'], true);

        if ($verifiedCaptured) {
            $this->markPaymentPaid($payment);
        } elseif (in_array($finalStatus, ['declined', 'expired', 'canceled', 'cancelled'], true)) {
            $this->markPaymentFailed($payment, (string) $finalStatus);
        }
        // Any other status (new/approved/authorised without a completed
        // capture yet) — leave local status alone, legitimate in-progress
        // state, not a final outcome either way.

        $payment->refresh();

        return [
            'previous_local_status' => $previousStatus,
            'tamara_status'         => $finalStatus,
            'new_local_status'      => $payment->status,
            'changed'               => $previousStatus !== $payment->status,
            'verified_captured'     => $verifiedCaptured,
        ];
    }

    private function markPaymentPaid(DirectAppointmentPayment $payment): void
    {
        if ($payment->status === 'paid') {
            return;
        }

        $payment->update(['status' => 'paid']);

        $appointment = DirectAppointment::where('sales_order_id', $payment->sales_order_id)
            ->orderByDesc('id')
            ->first();

        if (!$appointment) {
            Log::warning('DirectAppointment not found while marking Tamara payment paid.', [
                'payment_id'     => $payment->payment_id,
                'reference_id'   => $payment->reference_id,
                'sales_order_id' => $payment->sales_order_id,
            ]);
            return;
        }

        $appointment->collect = max(0, (float) $appointment->collect - (float) $payment->price);
        $appointment->save();

        $this->recalculateAppointment($payment);

        Log::info('Tamara payment marked as paid.', [
            'payment_id'   => $payment->payment_id,
            'reference_id' => $payment->reference_id,
        ]);
    }

    private function markPaymentFailed(DirectAppointmentPayment $payment, string $reason): void
    {
        if ($payment->status === 'paid') {
            Log::warning('Attempted to mark already paid Tamara payment as failed.', [
                'payment_id'   => $payment->payment_id,
                'reference_id' => $payment->reference_id,
                'reason'       => $reason,
            ]);
            return;
        }

        $payment->update(['status' => 'failed']);

        Log::info('Tamara payment marked as failed.', [
            'payment_id'   => $payment->payment_id,
            'reference_id' => $payment->reference_id,
            'reason'       => $reason,
        ]);
    }

    private function recalculateAppointment(DirectAppointmentPayment $payment): void
    {
        $appointment = DirectAppointment::where('sales_order_id', $payment->sales_order_id)
            ->orderByDesc('id')
            ->first();

        if (!$appointment) {
            return;
        }

        $paidSum  = (float) $appointment->payments()->where('status', 'paid')->sum('price');
        $discount = (float) ($appointment->discount ?? 0);
        $required = (float) ($appointment->required_amount ?? 0);

        $allPaid = $required > 0 && abs(($paidSum + $discount) - $required) < 0.01;

        if ($allPaid) {
            $appointment->update(['status' => 'paid', 'collect' => 0]);
        }
    }
}