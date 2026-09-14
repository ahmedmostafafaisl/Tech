<?php

namespace App\Jobs;

use App\Models\DirectAppointmentPayment;
use App\Models\TabbyWebhook;
use App\Services\Payment\TabbyPaymentSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessTabbyWebhook implements ShouldQueue
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

    public function handle(TabbyPaymentSyncService $syncService): void
    {
        $webhook = TabbyWebhook::find($this->webhookId);

        if (!$webhook) {
            Log::warning('Tabby webhook record not found.', ['webhook_id' => $this->webhookId]);
            return;
        }

        if ($webhook->processed_at) {
            Log::info('Tabby webhook already processed.', ['webhook_id' => $webhook->id]);
            return;
        }

        $payload = $webhook->payload ?? [];

        $tabbyPaymentId = $payload['id'] ?? null;
        $referenceId = data_get($payload, 'order.reference_id');

        if (!$tabbyPaymentId && !$referenceId) {
            Log::warning('Tabby webhook missing payment ID and reference ID.', [
                'webhook_id' => $webhook->id,
                'payload'    => $payload,
            ]);

            // Malformed notification — don't retry this one forever.
            $webhook->update(['processed_at' => now()]);
            return;
        }

        Log::info('Processing Tabby webhook.', [
            'webhook_id'   => $webhook->id,
            'payment_id'   => $tabbyPaymentId,
            'reference_id' => $referenceId,
        ]);

        $payment = null;

        if ($referenceId) {
            $payment = DirectAppointmentPayment::where('reference_id', $referenceId)->first();
        }

        if (!$payment && $tabbyPaymentId) {
            $payment = DirectAppointmentPayment::where('payment_id', $tabbyPaymentId)->first();
        }

        if (!$payment) {
            Log::warning('Local Tabby payment not found.', [
                'webhook_id'       => $webhook->id,
                'tabby_payment_id' => $tabbyPaymentId,
                'reference_id'     => $referenceId,
            ]);

            // Throw so the queue retries — useful if the webhook arrived
            // before our local payment record was fully committed.
            throw new \RuntimeException('DirectAppointmentPayment not found.');
        }

        if ($tabbyPaymentId && $payment->payment_id !== $tabbyPaymentId) {
            $payment->update(['payment_id' => $tabbyPaymentId]);
        }

        // Delegate the actual status reconciliation to the shared service
        // — never trusts the webhook's own status field, re-verifies
        // against Tabby's live API. Throws on an unrecognized/error
        // status, which we let propagate so this job's retry/backoff
        // mechanism gets a real chance to retry.
        $result = $syncService->syncPayment($payment);

        Log::info('Tabby webhook processed successfully.', [
            'webhook_id'   => $webhook->id,
            'payment_id'   => $payment->payment_id,
            'reference_id' => $payment->reference_id,
            'result'       => $result,
        ]);

        // Only mark processed on a genuinely successful, recognized
        // outcome — syncPayment() above already throws for unrecognized
        // statuses, so if we reach this line, it succeeded.
        $webhook->update(['processed_at' => now()]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Tabby webhook job failed permanently.', [
            'webhook_id' => $this->webhookId,
            'error'      => $exception->getMessage(),
            'trace'      => $exception->getTraceAsString(),
        ]);
    }
}
