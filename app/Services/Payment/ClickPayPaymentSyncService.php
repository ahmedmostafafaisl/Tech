<?php

namespace App\Services\Payment;

use App\Models\DirectAppointment;
use App\Models\DirectAppointmentPayment;
use Illuminate\Support\Facades\Log;

/**
 * Reconciles a DirectAppointmentPayment against ClickPay's actual, live
 * transaction status via queryPayment() — mirrors TabbyPaymentSyncService
 * and TamaraPaymentSyncService's role for their respective providers.
 *
 * Used by: the newly-added newHandleCallback() (fixing the previously
 * broken/missing async callback route), and the new manual sync endpoint.
 */
class ClickPayPaymentSyncService
{
    public function __construct(
        private readonly ClickPayService $clickPay,
    ) {}

    /**
     * @return array{
     *     previous_local_status: string,
     *     clickpay_status: string|null,
     *     new_local_status: string,
     *     changed: bool,
     *     verified_authorised: bool,
     * }
     */
    public function syncPayment(DirectAppointmentPayment $payment): array
    {
        $previousStatus = $payment->status;

        $tranRef = $payment->payment_id ?: $payment->reference_id;

        if (!$tranRef) {
            throw new \RuntimeException('No ClickPay transaction reference (payment_id/reference_id) on this payment record.');
        }

        $data = $this->clickPay->queryPayment($tranRef);

        $clickpayStatus = $data['payment_result']['response_message'] ?? null;

        Log::info('Syncing ClickPay payment status.', [
            'tran_ref'        => $tranRef,
            'reference_id'    => $payment->reference_id,
            'previous_status' => $previousStatus,
            'clickpay_status' => $clickpayStatus,
        ]);

        $verifiedAuthorised = $clickpayStatus === 'Authorised';

        if ($verifiedAuthorised) {
            $this->markPaymentPaid($payment);
        } elseif (in_array($clickpayStatus, ['Declined', 'Voided', 'Expired'], true)) {
            $this->markPaymentFailed($payment, (string) $clickpayStatus);
        }
        // Any other response (e.g. still Pending) — leave local status
        // alone, legitimate in-progress state.

        $payment->refresh();

        return [
            'previous_local_status' => $previousStatus,
            'clickpay_status'       => $clickpayStatus,
            'new_local_status'      => $payment->status,
            'changed'               => $previousStatus !== $payment->status,
            'verified_authorised'   => $verifiedAuthorised,
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
            Log::warning('DirectAppointment not found while marking ClickPay payment paid.', [
                'payment_id'     => $payment->payment_id,
                'reference_id'   => $payment->reference_id,
                'sales_order_id' => $payment->sales_order_id,
            ]);
            return;
        }

        $appointment->collect = max(0, (float) $appointment->collect - (float) $payment->price);
        $appointment->save();

        $this->recalculateAppointment($payment);

        Log::info('ClickPay payment marked as paid.', [
            'payment_id'   => $payment->payment_id,
            'reference_id' => $payment->reference_id,
        ]);
    }

    private function markPaymentFailed(DirectAppointmentPayment $payment, string $reason): void
    {
        if ($payment->status === 'paid') {
            Log::warning('Attempted to mark already paid ClickPay payment as failed.', [
                'payment_id'   => $payment->payment_id,
                'reference_id' => $payment->reference_id,
                'reason'       => $reason,
            ]);
            return;
        }

        $payment->update(['status' => 'failed']);

        Log::info('ClickPay payment marked as failed.', [
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

        // ✅ New required_amount calculation (Step 7 — same shared
        // calculator/flag/fetcher as Tabby/Tamara's recalculateAppointment()
        // in Steps 5-6). Same note applies: $required below reflects
        // DY365's view (net of PaidAmount/used_balance) once fetched,
        // while $paidSum above stays the LOCAL sum of this app's own
        // paid DirectAppointmentPayment rows.
        if (\App\Models\Setting::isActive('new_required_amount_calculation_active')) {
            $appointmentData = app(DynamicsAppointmentDataFetcher::class)->fetch($appointment);

            $dyRequiredAmount = (float) ($appointmentData['required_amount'] ?? $required);
            $paidAmount       = (float) ($appointmentData['PaidAmount'] ?? 0);
            $usedBalance      = (float) ($appointmentData['used_balance'] ?? 0);

            $required = app(RequiredAmountCalculator::class)
                ->calculate($dyRequiredAmount, $paidAmount, $usedBalance);
        }

        $allPaid = $required > 0 && abs(($paidSum + $discount) - $required) < 0.01;

        if ($allPaid) {
            $appointment->update(['status' => 'paid', 'collect' => 0]);
        }
    }
}
