<?php

namespace App\Console\Commands;

use App\Jobs\SendInvoiceWhatsAppMessageJob;
use App\Models\InvoiceWhatsAppMessage;
use Illuminate\Console\Command;

/**
 * Runs daily at 1 AM (see Kernel.php). For every InvoiceWhatsAppMessage still marked 'failed' that was
 * created in the last 24 hours, re-dispatches SendInvoiceWhatsAppMessageJob against that SAME row (via
 * $existingMessageId) — the exact same send logic (DY365 check, then the WhatsApp send) as the original
 * attempt, updating the existing record rather than creating a new one, so retries don't pile up duplicate
 * rows for the same failed send.
 *
 * Scoped to the last 24 hours deliberately, mirroring RetryPendingInvoiceLinksCommand for the existing
 * cycle: a message still failing a day later is a different, probably-manual-intervention problem, not
 * something to keep silently retrying forever every day.
 */
class RetryFailedInvoiceWhatsAppMessagesCommand extends Command
{
    protected $signature = 'invoice-whatsapp:retry-failed-messages';

    protected $description = 'Resend invoice WhatsApp messages (new cycle) still marked failed, for messages created in the last 24 hours';

    public function handle(): int
    {
        $candidates = InvoiceWhatsAppMessage::where('status', 'failed')
            ->where('created_at', '>=', now()->subDay())
            ->get();

        $this->info("Found {$candidates->count()} failed invoice WhatsApp message(s) to retry.");

        foreach ($candidates as $message) {
            SendInvoiceWhatsAppMessageJob::dispatch($message->book_id, $message->phone, $message->id);
        }

        return self::SUCCESS;
    }
}
