<?php

namespace App\Jobs;

use App\Models\DirectAppointmentPayment;
use App\Models\NearPayWebhook;
use App\Services\Payment\NearPayPaymentSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Mirrors ProcessTabbyWebhook exactly: delegate the actual status reconciliation to the shared sync
 * service, which never trusts the webhook's own fields and always re-verifies against NearPay's live API.
 *
 * The one real difference from Tabby: NearPay's webhook carries no reference back to a local order, so the
 * ONLY way a local payment is ever associated with a transaction id is NearPayPaymentController::confirm()
 * claiming it first (see that controller's docblock). If this webhook arrives before that claim has
 * happened — a real possibility, since the client callback and the webhook are two independent,
 * unordered deliveries — there is nothing to match yet. That is treated as a transient, retryable
 * condition (same backoff as Tabby), not a permanent failure: if the claim still hasn't happened after all
 * retries are exhausted, failed() logs it as an unverifiable transaction for manual follow-up, which is
 * the most this job can safely do without guessing which order it belongs to.
 */
class ProcessNearPayWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public array $backoff = [30, 60, 120, 300, 600];

    public function __construct(
        public int $webhookId
    ) {}

    public function handle(NearPayPaymentSyncService $syncService): void
    {
        $webhook = NearPayWebhook::find($this->webhookId);

        if (!$webhook) {
            Log::warning('NearPay webhook record not found.', ['webhook_id' => $this->webhookId]);

            return;
        }

        if ($webhook->processed_at) {
            Log::info('NearPay webhook already processed.', ['webhook_id' => $webhook->id]);

            return;
        }

        if (!$webhook->transaction_id && !$webhook->customer_reference_number) {
            Log::warning('NearPay webhook missing both transaction id and customer reference number.', ['webhook_id' => $webhook->id]);
            $webhook->update(['processed_at' => now()]);

            return;
        }

        // customer_reference_number (this app's own reference_id, echoed back by a real device) is tried
        // FIRST — a direct match that does not depend on confirm()/handleNfcResponse() having claimed the
        // transaction id yet. transaction_id (the existing payment_id match) is the fallback, for deliveries
        // where customer_reference_number is absent (NearPay's own examples all show it null) or simply
        // hasn't been wired up on the sending side for a given flow.
        $payment = null;

        if ($webhook->customer_reference_number) {
            $payment = DirectAppointmentPayment::where('payment_type', 'NEARPAY')
                ->where('reference_id', $webhook->customer_reference_number)
                ->first();
        }

        if (!$payment && $webhook->transaction_id) {
            $payment = DirectAppointmentPayment::where('payment_type', 'NEARPAY')
                ->where('payment_id', $webhook->transaction_id)
                ->first();
        }

        if (!$payment) {
            Log::warning('Local NearPay payment not yet claimed for this webhook.', [
                'webhook_id'                 => $webhook->id,
                'transaction_id'             => $webhook->transaction_id,
                'customer_reference_number'  => $webhook->customer_reference_number,
            ]);

            // Throw so the queue retries — the client's confirm()/handleNfcResponse() call may simply not
            // have landed yet.
            throw new \RuntimeException('No local NearPay payment is linked to this webhook yet.');
        }

        // Found via customer_reference_number but never actually claimed (payment_id still null) — this can
        // happen if the webhook races ahead of the client's own confirm()/handleNfcResponse() call. Claim it
        // here, under the same lock-and-check discipline NearPayPaymentController::claimTransaction() uses
        // for the HTTP path, so a transaction id NearPay reports can never silently end up linked to two
        // different local payments.
        if (!$payment->payment_id && $webhook->transaction_id) {
            DB::transaction(function () use ($payment, $webhook) {
                $locked = DirectAppointmentPayment::where('id', $payment->id)->lockForUpdate()->first();

                if (!$locked || $locked->payment_id !== null) {
                    return; // already claimed by something else since we loaded it — leave it alone
                }

                $reusedElsewhere = DirectAppointmentPayment::where('payment_type', 'NEARPAY')
                    ->where('payment_id', $webhook->transaction_id)
                    ->where('id', '!=', $locked->id)
                    ->lockForUpdate()
                    ->exists();

                if ($reusedElsewhere) {
                    Log::warning('NearPay webhook transaction id already claimed by a different payment — not overwriting.', [
                        'webhook_id'     => $webhook->id,
                        'transaction_id' => $webhook->transaction_id,
                        'reference_id'   => $locked->reference_id,
                    ]);

                    return;
                }

                $locked->update(['payment_id' => $webhook->transaction_id]);
            });

            $payment->refresh();
        }

        if (!$payment->payment_id) {
            // Either there was never a transaction_id to claim with, or the claim above was skipped because
            // this transaction id already belongs to a different payment — either way there is nothing to
            // verify against, and retrying will not change that. Mark processed rather than throwing: this
            // is a permanent condition, not a transient one (unlike the "not yet claimed at all" case above,
            // which IS worth retrying).
            Log::warning('NearPay webhook: payment has no transaction id to verify, after the claim attempt.', [
                'webhook_id'     => $webhook->id,
                'transaction_id' => $webhook->transaction_id,
                'reference_id'   => $payment->reference_id,
            ]);

            $webhook->update(['processed_at' => now()]);

            return;
        }

        Log::info('Processing NearPay webhook.', [
            'webhook_id'     => $webhook->id,
            'transaction_id' => $webhook->transaction_id,
            'reference_id'   => $payment->reference_id,
        ]);

        $result = $syncService->syncPayment($payment);

        Log::info('NearPay webhook processed.', [
            'webhook_id'     => $webhook->id,
            'transaction_id' => $webhook->transaction_id,
            'reference_id'   => $payment->reference_id,
            'result'         => $result,
        ]);

        $webhook->update(['processed_at' => now()]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('NearPay webhook job failed permanently — transaction could not be matched to a local payment and verified.', [
            'webhook_id' => $this->webhookId,
            'error'      => $exception->getMessage(),
        ]);
    }
}
