<?php

namespace App\Services\Payment;

use App\Models\DirectAppointment;
use App\Models\DirectAppointmentPayment;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

/**
 * Reconciles a DirectAppointmentPayment against AlinmaPay's actual, live transaction status via
 * inquiry() — the exact same role ClickPayPaymentSyncService plays for ClickPay (and
 * TabbyPaymentSyncService/TamaraPaymentSyncService for theirs). Deliberately re-queries AlinmaPay rather
 * than trusting the receipt callback's own decrypted body directly, for the same reason ClickPay's
 * newHandleCallback does: one shared, re-usable "ask the gateway what's actually true right now" method,
 * callable from both the callback and a manual/polling sync endpoint.
 */
class AlinmaPayPaymentSyncService
{
    public function __construct(
        private readonly AlinmaPayService $alinmaPay,
    ) {}

    /**
     * @return array{
     *     previous_local_status: string,
     *     alinmapay_response_code: string|null,
     *     new_local_status: string,
     *     changed: bool,
     *     verified_authorised: bool,
     * }
     */
    public function syncPayment(DirectAppointmentPayment $payment): array
    {
        $previousStatus = $payment->status;

        // order.orderId was set to reference_id when this payment was created (see
        // AlinmaPayService::createInvoiceNew()) — inquiry() needs that same value back, not payment_id
        // (AlinmaPay's own transaction id), which plays no part in identifying the original order.
        $orderId = $payment->reference_id;

        if (! $orderId) {
            throw new \RuntimeException('No AlinmaPay order reference (reference_id) on this payment record.');
        }

        $data = $this->alinmaPay->inquiry($orderId, $orderId, (float) $payment->price);

        $responseCode = isset($data['responseCode']) ? (string) $data['responseCode'] : null;

        Log::info('Syncing AlinmaPay payment status.', [
            'order_id'         => $orderId,
            'previous_status'  => $previousStatus,
            'alinmapay_status' => $responseCode,
        ]);

        // "000"/"001" — see AlinmaPayController's own note on these being the spec's success codes
        // (Section 14: "000 Transaction Successful", "001 Transaction Approved").
        $verifiedAuthorised = in_array($responseCode, ['000', '001'], true);

        if ($verifiedAuthorised) {
            $this->markPaymentPaid($payment);
        } elseif ($responseCode !== null && ! in_array($responseCode, ['000', '001'], true)) {
            // Any other real response code from AlinmaPay — not "no usable answer at all" — is treated as a
            // genuine non-success outcome, same as ClickPay's Declined/Voided/Expired set.
            $this->markPaymentFailed($payment, $responseCode);
        }
        // No usable response code at all — leave local status alone, legitimate in-progress/unknown state.

        $payment->refresh();

        return [
            'previous_local_status'   => $previousStatus,
            'alinmapay_response_code' => $responseCode,
            'new_local_status'        => $payment->status,
            'changed'                 => $previousStatus !== $payment->status,
            'verified_authorised'     => $verifiedAuthorised,
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

        if (! $appointment) {
            Log::warning('DirectAppointment not found while marking AlinmaPay payment paid.', [
                'payment_id'     => $payment->payment_id,
                'reference_id'   => $payment->reference_id,
                'sales_order_id' => $payment->sales_order_id,
            ]);

            return;
        }

        $appointment->collect = max(0, (float) $appointment->collect - (float) $payment->price);
        $appointment->save();

        $this->recalculateAppointment($payment);

        Log::info('AlinmaPay payment marked as paid.', [
            'payment_id'   => $payment->payment_id,
            'reference_id' => $payment->reference_id,
        ]);
    }

    private function markPaymentFailed(DirectAppointmentPayment $payment, string $reason): void
    {
        if ($payment->status === 'paid') {
            Log::warning('Attempted to mark already paid AlinmaPay payment as failed.', [
                'payment_id'   => $payment->payment_id,
                'reference_id' => $payment->reference_id,
                'reason'       => $reason,
            ]);

            return;
        }

        $payment->update(['status' => 'failed']);

        Log::info('AlinmaPay payment marked as failed.', [
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

        if (! $appointment) {
            return;
        }

        $paidSum  = (float) $appointment->payments()->where('status', 'paid')->sum('price');
        $discount = (float) ($appointment->discount ?? 0);
        $required = (float) ($appointment->required_amount ?? 0);

        // Same shared calculator/flag/fetcher as ClickPay/Tabby/Tamara's own recalculateAppointment() —
        // single source of truth, not duplicated here.
        if (Setting::isActive('new_required_amount_calculation_active')) {
            $appointmentData = app(DynamicsAppointmentDataFetcher::class)->fetch($appointment);

            $dyRequiredAmount = (float) ($appointmentData['required_amount'] ?? $required);
            $paidAmount       = (float) ($appointmentData['PaidAmount'] ?? 0);
            $usedBalance      = abs((float) ($appointmentData['used_balance'] ?? 0));

            $required = app(RequiredAmountCalculator::class)
                ->calculate($dyRequiredAmount, $paidAmount, $usedBalance);
        }

        $allPaid = $required > 0 && abs(($paidSum + $discount) - $required) < 0.01;

        if ($allPaid) {
            $appointment->update(['status' => 'paid', 'collect' => 0]);
        }
    }
}
