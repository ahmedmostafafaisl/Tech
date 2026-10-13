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
    ) {
    }

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

        if (!$webhook->transaction_id) {
            Log::warning('NearPay webhook missing transaction id.', ['webhook_id' => $webhook->id]);
            $webhook->update(['processed_at' => now()]);

            return;
        }

        $payment = DirectAppointmentPayment::where('payment_type', 'NEARPAY')
            ->where('payment_id', $webhook->transaction_id)
            ->first();

        if (!$payment) {
            Log::warning('Local NearPay payment not yet claimed for this transaction id.', [
                'webhook_id'     => $webhook->id,
                'transaction_id' => $webhook->transaction_id,
            ]);

            // Throw so the queue retries — the client's confirm() call may simply not have landed yet.
            throw new \RuntimeException('No local NearPay payment is linked to this transaction id yet.');
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
