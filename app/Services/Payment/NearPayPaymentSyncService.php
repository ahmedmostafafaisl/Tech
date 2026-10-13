<?php

namespace App\Services\Payment;

use App\Models\DirectAppointment;
use App\Models\DirectAppointmentPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reconciles a DirectAppointmentPayment against NearPay's actual, live transaction state — never trusting
 * a client success callback or a webhook's own fields without confirming against the Banks API first.
 *
 * Mirrors TabbyPaymentSyncService's shape deliberately (both the browser-callback controller and the
 * webhook job call this same method, exactly like Tabby's two callers share one service), but is its own,
 * separate class rather than a shared base: TabbyPaymentSyncService is existing, already-relied-on code,
 * and this integration's own instruction is to make the smallest safe set of changes — not to refactor a
 * working gateway's code to share a base class it was never built to share. The `markPaid` /
 * `recalculateAppointment` logic below is intentionally the same shape as Tabby's for that reason, not
 * because it was extracted from it.
 *
 * The HTTP call to NearPay always happens BEFORE any database transaction opens — only the short, final,
 * row-locked status update is ever done inside one.
 */
class NearPayPaymentSyncService
{
    public function __construct(
        private readonly NearPayService $nearPay,
    ) {}

    /**
     * Fetches the payment's CURRENT transaction state from NearPay and applies whatever local change that
     * implies. Never downgrades an already-paid/refunded/reversed payment to failed; a genuine later
     * refund/reversal CAN still transition a paid payment, since that is a real subsequent event, not a
     * stale conflicting one.
     *
     * @return array{previous_local_status: string, verdict: string, new_local_status: string, changed: bool}
     */
    public function syncPayment(DirectAppointmentPayment $payment): array
    {
        $previousStatus = $payment->status;

        if (!$payment->payment_id) {
            throw new \RuntimeException('NearPay transaction ID is missing on this payment record.');
        }

        $transaction = $this->nearPay->getTransaction($payment->payment_id);

        if ($transaction === null) {
            // A confirmed 404 right now does not prove the transaction will never exist — NearPay's own
            // read-API can lag a moment behind a transaction that only just happened. Treated the same as
            // any other inconclusive result: change nothing, let a retry resolve it either way.
            Log::warning('NearPay transaction not found (yet) during verification.', [
                'payment_id'   => $payment->payment_id,
                'reference_id' => $payment->reference_id,
            ]);

            return $this->unchanged($payment, $previousStatus, 'not_found');
        }

        $result = NearPayTransactionVerification::evaluate($transaction, (string) $payment->price, 'SAR');

        Log::info('Syncing NearPay payment status.', [
            'payment_id'      => $payment->payment_id,
            'reference_id'    => $payment->reference_id,
            'previous_status' => $previousStatus,
            'verdict'         => $result['verdict'],
            'reason'          => $result['reason'],
        ]);

        switch ($result['verdict']) {
            case NearPayTransactionVerification::PAID:
                $this->markPaid($payment);
                break;

            case NearPayTransactionVerification::REFUNDED:
                $this->markTerminal($payment, 'refunded');
                break;

            case NearPayTransactionVerification::REVERSED:
                $this->markTerminal($payment, 'reversed');
                break;

            case NearPayTransactionVerification::REJECTED:
                $this->markFailure($payment);
                break;

            case NearPayTransactionVerification::MISMATCH:
                // Flagged for investigation, never silently treated as paid or failed — the status is left
                // exactly as it was.
                Log::critical('NearPay verification mismatch — left unchanged for investigation.', [
                    'payment_id'   => $payment->payment_id,
                    'reference_id' => $payment->reference_id,
                    'reason'       => $result['reason'],
                ]);

                DirectAppointmentPayment::where('id', $payment->id)->update(['verification_note' => $result['reason']]);
                break;

            case NearPayTransactionVerification::INCONCLUSIVE:
            default:
                Log::warning('NearPay verification was inconclusive — left unchanged.', [
                    'payment_id'   => $payment->payment_id,
                    'reference_id' => $payment->reference_id,
                    'reason'       => $result['reason'],
                ]);
                break;
        }

        $payment->refresh();

        return [
            'previous_local_status' => $previousStatus,
            'verdict'                => $result['verdict'],
            'new_local_status'       => $payment->status,
            'changed'                => $previousStatus !== $payment->status,
        ];
    }

    /** @return array{previous_local_status: string, verdict: string, new_local_status: string, changed: bool} */
    private function unchanged(DirectAppointmentPayment $payment, string $previousStatus, string $verdict): array
    {
        return [
            'previous_local_status' => $previousStatus,
            'verdict'                => $verdict,
            'new_local_status'       => $previousStatus,
            'changed'                => false,
        ];
    }

    /** Mark payment PAID. Idempotent; also recalculates the whole appointment. */
    private function markPaid(DirectAppointmentPayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $locked = DirectAppointmentPayment::where('id', $payment->id)->lockForUpdate()->first();

            if (!$locked) {
                return;
            }

            if (in_array($locked->status, ['refunded', 'reversed'], true)) {
                Log::warning('NearPay reported paid for a payment already refunded/reversed locally — left unchanged.', [
                    'payment_id'   => $locked->payment_id,
                    'reference_id' => $locked->reference_id,
                    'local_status' => $locked->status,
                ]);

                return;
            }

            if ($locked->status === 'paid') {
                $this->recalculateAppointment($locked);

                return;
            }

            $locked->update(['status' => 'paid', 'verification_note' => null]);

            $appointment = DirectAppointment::where('sales_order_id', $locked->sales_order_id)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if ($appointment) {
                $appointment->collect = max(0, (float) $appointment->collect - (float) $locked->price);
                $appointment->save();
            }

            $this->recalculateAppointment($locked);
        });

        Log::info('NearPay payment marked as paid.', ['payment_id' => $payment->payment_id, 'reference_id' => $payment->reference_id]);
    }

    /**
     * Mark payment as a refund or a reversal. Both are terminal: once set, nothing (including a later PAID
     * verdict for the very same transaction) can move it back to paid — a confirmed refund/reversal is the
     * provider's own final word on this money.
     */
    private function markTerminal(DirectAppointmentPayment $payment, string $terminalStatus): void
    {
        DB::transaction(function () use ($payment, $terminalStatus) {
            $locked = DirectAppointmentPayment::where('id', $payment->id)->lockForUpdate()->first();

            if (!$locked || $locked->status === $terminalStatus) {
                return;
            }

            if (in_array($locked->status, ['refunded', 'reversed'], true)) {
                // Already in a different terminal state — do not flip between refunded and reversed.
                return;
            }

            $wasPaid = $locked->status === 'paid';

            $locked->update(['status' => $terminalStatus, 'verification_note' => null]);

            if ($wasPaid) {
                // Money that was counted as collected is no longer the customer's payment — give the
                // amount back to the appointment's outstanding balance.
                $appointment = DirectAppointment::where('sales_order_id', $locked->sales_order_id)
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if ($appointment) {
                    $appointment->collect = (float) $appointment->collect + (float) $locked->price;
                    $appointment->save();
                }
            }
        });

        Log::info('NearPay payment marked as ' . $terminalStatus . '.', ['payment_id' => $payment->payment_id, 'reference_id' => $payment->reference_id]);
    }

    /** Mark payment as failed. Never downgrades an already-paid/refunded/reversed payment. */
    private function markFailure(DirectAppointmentPayment $payment): void
    {
        if (in_array($payment->status, ['paid', 'refunded', 'reversed'], true)) {
            Log::warning('NearPay reported rejected for a payment already in a later terminal state — left unchanged.', [
                'payment_id'   => $payment->payment_id,
                'reference_id' => $payment->reference_id,
                'local_status' => $payment->status,
            ]);

            return;
        }

        $payment->update(['status' => 'failed', 'verification_note' => null]);

        Log::info('NearPay payment marked as failed.', ['payment_id' => $payment->payment_id, 'reference_id' => $payment->reference_id]);
    }

    /** Recalculates whether the whole appointment is now fully paid — same rule as Tabby's equivalent. */
    private function recalculateAppointment(DirectAppointmentPayment $payment): void
    {
        $appointment = DirectAppointment::where('sales_order_id', $payment->sales_order_id)->orderByDesc('id')->first();

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

            Log::info('Direct appointment fully paid (NearPay).', [
                'sales_order_id' => $payment->sales_order_id,
                'paid_sum'       => $paidSum,
                'discount'       => $discount,
                'required'       => $required,
            ]);
        }
    }
}
