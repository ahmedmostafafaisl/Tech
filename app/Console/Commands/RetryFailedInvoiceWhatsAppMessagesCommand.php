<?php

namespace App\Console\Commands;

use App\Jobs\SendInvoiceWhatsAppMessageJob;
use App\Models\InvoiceWhatsAppMessage;
use Illuminate\Console\Command;

/**
 * Runs daily at 1 AM (see Kernel.php). Catches every unsent invoice regardless of why — a confirmed failure
 * (status='failed': DY365 had no invoice, a network error, Meta rejected the template, any other error the
 * send attempt recorded) and a stuck, stale 'pending' row (the job started but never reached a final state —
 * crashed, timed out, or the worker died mid-attempt) are both eligible. Never touches a row already
 * accepted by Meta or delivered: status='sent' means message_id was set, i.e. Meta already accepted it, and
 * is excluded unconditionally, regardless of its delivery_status — resending that would duplicate a message
 * the customer may have already received.
 *
 * Concurrency: retrying_at is a per-row lock. Each eligible row is claimed with a conditional update (only
 * succeeds if retrying_at is still null) before being dispatched, so two overlapping runs of this command
 * (or this command running while a row's original send is still in flight) can never both pick up and
 * resend the same row. SendInvoiceWhatsAppMessageJob clears retrying_at itself once an attempt finishes,
 * success or failure.
 */
class RetryFailedInvoiceWhatsAppMessagesCommand extends Command
{
    protected $signature = 'invoice-whatsapp:retry-failed-messages';

    protected $description = 'Resend invoice WhatsApp messages (new cycle) that failed or got stuck pending, for messages created in the last 24 hours';

    /**
     * A 'pending' row older than this is treated as stuck (the job that created it never reached a final
     * state) rather than still legitimately in flight — ordinary sends finish in seconds, not minutes.
     */
    private const STALE_PENDING_MINUTES = 15;

    public function handle(): int
    {
        $since = now()->subDay();

        $candidates = InvoiceWhatsAppMessage::whereNull('retrying_at')
            ->where('created_at', '>=', $since)
            ->where(function ($query) {
                $query->where('status', 'failed')
                    ->orWhere(function ($query) {
                        $query->where('status', 'pending')
                            ->where('updated_at', '<=', now()->subMinutes(self::STALE_PENDING_MINUTES));
                    });
            })
            ->get();

        $this->info("Found {$candidates->count()} invoice WhatsApp message(s) eligible for retry.");

        $claimed = 0;

        foreach ($candidates as $message) {
            // Atomic claim: succeeds only if retrying_at is still null for this exact row — if a concurrent
            // run (or anything else) already claimed it between the query above and here, this affects 0
            // rows and the row is simply skipped, not retried twice.
            $wasClaimed = InvoiceWhatsAppMessage::where('id', $message->id)
                ->whereNull('retrying_at')
                ->update(['retrying_at' => now()]);

            if (! $wasClaimed) {
                continue;
            }

            $claimed++;

            SendInvoiceWhatsAppMessageJob::dispatch($message->book_id, $message->phone, $message->id);
        }

        $this->info("Claimed and dispatched {$claimed} retry attempt(s).");

        return self::SUCCESS;
    }
}
