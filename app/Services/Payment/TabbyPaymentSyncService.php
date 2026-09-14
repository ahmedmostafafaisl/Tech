<?php

namespace App\Services\Payment;

use App\Models\DirectAppointment;
use App\Models\DirectAppointmentPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reconciles a DirectAppointmentPayment against Tabby's actual, live
 * payment status — never trusting a webhook's status field, or any
 * caller's assumption, without confirming against Tabby's Retrieve
 * Payment API first.
 *
 * Extracted from ProcessTabbyWebhook so this logic has exactly one
 * home: the webhook job calls it when a webhook arrives, and
 * TabbyPaymentController::syncPaymentStatus() calls it on demand for
 * manual re-checks — both go through the same code, so a fix here
 * fixes both callers at once.
 */
class TabbyPaymentSyncService
{
    public function __construct(
        private readonly TabbyService $tabby,
    ) {}

    /**
     * Fetches the payment's CURRENT status from Tabby and applies
     * whatever local state changes that status implies.
     *
     * @return array{
     *     previous_local_status: string,
     *     tabby_status: string,
     *     new_local_status: string,
     *     changed: bool,
     * }
     */
    public function syncPayment(DirectAppointmentPayment $payment): array
    {
        $previousStatus = $payment->status;

        if (!$payment->payment_id) {
            throw new \RuntimeException('Tabby payment ID is missing on this payment record.');
        }

        $tabbyPayment = $this->tabby->retrieveTabbyPayment($payment->payment_id);

        if (!is_array($tabbyPayment)) {
            throw new \RuntimeException('Invalid response from Tabby Retrieve Payment.');
        }

        $actualStatus = strtoupper(trim($tabbyPayment['status'] ?? ''));

        Log::info('Syncing Tabby payment status.', [
            'payment_id'      => $payment->payment_id,
            'reference_id'    => $payment->reference_id,
            'previous_status' => $previousStatus,
            'tabby_status'    => $actualStatus,
        ]);

        switch ($actualStatus) {
            case 'AUTHORIZED':
                $this->handleAuthorized($payment, $tabbyPayment);
                break;

            case 'CLOSED':
                $this->markPaymentPaid($payment);
                break;

            case 'REJECTED':
                $this->markPaymentFailure($payment, 'REJECTED');
                break;

            case 'EXPIRED':
                $this->markPaymentFailure($payment, 'EXPIRED');
                break;

            case 'FAILED':
                $this->markPaymentFailure($payment, 'FAILED');
                break;

            default:
                Log::warning('Unhandled/unexpected Tabby payment status during sync.', [
                    'payment_id'   => $payment->payment_id,
                    'reference_id' => $payment->reference_id,
                    'actual_status' => $actualStatus,
                ]);

                throw new \RuntimeException(
                    "Unhandled/unexpected Tabby payment status: {$actualStatus}"
                );
        }

        $payment->refresh();

        return [
            'previous_local_status' => $previousStatus,
            'tabby_status'          => $actualStatus,
            'new_local_status'      => $payment->status,
            'changed'               => $previousStatus !== $payment->status,
        ];
    }

    /**
     * AUTHORIZED != PAID. Attempts capture if none exists yet.
     */
    private function handleAuthorized(DirectAppointmentPayment $payment, array $tabbyPayment): void
    {
        if ($payment->status !== 'paid') {
            $payment->update(['status' => 'pending']);
        }

        $captures = $tabbyPayment['captures'] ?? [];

        if (!empty($captures)) {
            Log::info('Tabby payment already has capture.', [
                'payment_id'     => $payment->payment_id,
                'reference_id'   => $payment->reference_id,
                'captures_count' => count($captures),
            ]);

            return;
        }

        Log::info('Attempting Tabby payment capture.', [
            'payment_id'   => $payment->payment_id,
            'reference_id' => $payment->reference_id,
            'amount'       => $payment->price,
        ]);

        $captureResponse = $this->tabby->capturePaymentRequest(
            $payment->payment_id,
            $payment->reference_id,
            $payment->price
        );

        if (!is_array($captureResponse)) {
            throw new \RuntimeException('Invalid response from Tabby Capture Payment.');
        }

        $captureStatus = strtoupper(trim($captureResponse['status'] ?? ''));

        Log::info('Tabby capture response.', [
            'payment_id'     => $payment->payment_id,
            'reference_id'   => $payment->reference_id,
            'capture_status' => $captureStatus,
        ]);

        if ($captureStatus === 'CLOSED') {
            $this->markPaymentPaid($payment);
            return;
        }

        if (in_array($captureStatus, ['AUTHORIZED', 'PENDING'], true)) {
            if ($payment->status !== 'paid') {
                $payment->update(['status' => 'pending']);
            }
            return;
        }

        if (in_array($captureStatus, ['REJECTED', 'FAILED', 'EXPIRED'], true)) {
            $this->markPaymentFailure($payment, 'CAPTURE_' . $captureStatus);
            return;
        }

        Log::warning('Unhandled Tabby capture status.', [
            'payment_id'     => $payment->payment_id,
            'reference_id'   => $payment->reference_id,
            'capture_status' => $captureStatus,
        ]);
    }

    /**
     * Mark payment as PAID. Idempotent.
     */
    private function markPaymentPaid(DirectAppointmentPayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $lockedPayment = DirectAppointmentPayment::where('id', $payment->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedPayment) {
                return;
            }

            if ($lockedPayment->status === 'paid') {
                Log::info('Payment already marked as paid.', [
                    'payment_id'   => $lockedPayment->payment_id,
                    'reference_id' => $lockedPayment->reference_id,
                ]);

                $this->recalculateAppointment($lockedPayment);
                return;
            }

            $lockedPayment->update(['status' => 'paid']);

            $appointment = DirectAppointment::where('sales_order_id', $lockedPayment->sales_order_id)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (!$appointment) {
                Log::warning('DirectAppointment not found while marking payment paid.', [
                    'payment_id'     => $lockedPayment->payment_id,
                    'reference_id'   => $lockedPayment->reference_id,
                    'sales_order_id' => $lockedPayment->sales_order_id,
                ]);

                return;
            }

            $appointment->collect = max(0, (float) $appointment->collect - (float) $lockedPayment->price);
            $appointment->save();

            $this->recalculateAppointment($lockedPayment);
        });

        Log::info('Tabby payment marked as paid.', [
            'payment_id'   => $payment->payment_id,
            'reference_id' => $payment->reference_id,
        ]);
    }

    /**
     * Mark payment as failure. Never downgrades an already-paid payment.
     */
    private function markPaymentFailure(DirectAppointmentPayment $payment, string $reason): void
    {
        if ($payment->status === 'paid') {
            Log::warning('Attempted to mark already paid payment as failure.', [
                'payment_id'   => $payment->payment_id,
                'reference_id' => $payment->reference_id,
                'reason'       => $reason,
            ]);

            return;
        }

        $payment->update(['status' => 'failure']);

        Log::info('Tabby payment marked as failure.', [
            'payment_id'   => $payment->payment_id,
            'reference_id' => $payment->reference_id,
            'reason'       => $reason,
        ]);
    }

    /**
     * Recalculates whether the whole appointment is now fully paid.
     */
    private function recalculateAppointment(DirectAppointmentPayment $payment): void
    {
        $appointment = DirectAppointment::where('sales_order_id', $payment->sales_order_id)
            ->orderByDesc('id')
            ->first();

        if (!$appointment) {
            Log::warning('Appointment not found while recalculating payment state.', [
                'sales_order_id' => $payment->sales_order_id,
                'payment_id'     => $payment->payment_id,
            ]);

            return;
        }

        $paidSum  = (float) $appointment->payments()->where('status', 'paid')->sum('price');
        $discount = (float) ($appointment->discount ?? 0);
        $required = (float) ($appointment->required_amount ?? 0);

        $allPaid = $required > 0 && abs(($paidSum + $discount) - $required) < 0.01;

        if ($allPaid) {
            $appointment->update(['status' => 'paid', 'collect' => 0]);

            Log::info('Direct appointment fully paid.', [
                'sales_order_id' => $payment->sales_order_id,
                'paid_sum'       => $paidSum,
                'discount'       => $discount,
                'required'       => $required,
            ]);
        }

        // Not fully paid — intentionally leave appointment status alone,
        // it may have its own business states.
    }
}
