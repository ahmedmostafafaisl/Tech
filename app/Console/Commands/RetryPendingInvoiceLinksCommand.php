<?php

namespace App\Console\Commands;

use App\Jobs\SendInvoiceLinkJob;
use App\Models\ShortLink;
use Illuminate\Console\Command;

/**
 * Runs daily (see Kernel.php). SendInvoiceLinkJob saves a ShortLink immediately, even when DY365 doesn't
 * have an invoice yet (invoice_generated = false) — this command is what checks back later: for every
 * such link created in the last 24 hours, it re-dispatches the exact same job, which re-checks DY365 and,
 * if an invoice now exists, sends the message and marks the link sent — same code path as the original
 * attempt, no separate logic to keep in sync.
 *
 * Scoped to the last 24 hours deliberately — an invoice that still doesn't exist a day after the
 * appointment is a different, probably-manual-intervention problem, not something to keep silently retrying
 * forever every day.
 */
class RetryPendingInvoiceLinksCommand extends Command
{
    protected $signature = 'invoices:retry-pending-links';

    protected $description = 'Resend invoice links whose invoice was not yet generated in DY365 at send time, for links created in the last 24 hours';

    public function handle(): int
    {
        $candidates = ShortLink::where('invoice_generated', false)
            ->where('created_at', '>=', now()->subDay())
            ->whereNotNull('book_id')
            ->whereNotNull('phone')
            ->get();

        $this->info("Found {$candidates->count()} pending invoice link(s) to retry.");

        foreach ($candidates as $link) {
            SendInvoiceLinkJob::dispatch($link->book_id, $link->phone);
        }

        return self::SUCCESS;
    }
}
