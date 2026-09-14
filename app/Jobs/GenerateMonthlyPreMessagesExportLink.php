<?php

namespace App\Jobs;

use App\Exports\PreAppointmentMessagesPowerBiExport;
use App\Models\PowerBiExportLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Runs on schedule (daily 12:00 PM Riyadh time) to (re)generate the
 * CURRENT month's export and persist its link. Also reused directly
 * (bypassing the queue, as a plain synchronous method call) by the
 * "last month" route as a fallback when no stored link exists yet.
 *
 * One file per month, not per run: the S3 path is deterministic based
 * only on $month, so every day's run overwrites the SAME object — no
 * accumulation of stale files. A new file only gets created the moment
 * $month itself changes (i.e. the first run of a new calendar month).
 */
class GenerateMonthlyPreMessagesExportLink implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly ?string $month = null,
    ) {}

    /**
     * Entry point when dispatched through the queue (the daily schedule).
     * Defaults to the current month if none was passed to the constructor.
     */
    public function handle(): void
    {
        $month = $this->month ?? now('Asia/Riyadh')->subMonthNoOverflow()->format('Y-m');

        try {
            $this->generate($month);
        } catch (\Throwable $e) {
        }
    }

    /**
     * The actual work: (re)build the export for a given month, upload to
     * S3 at a path keyed only by month (so reruns overwrite in place),
     * persist the link (upsert by month), and return the URL.
     *
     * Public and callable directly (not just via dispatch) so a route can
     * invoke this synchronously as an on-demand fallback without going
     * through the queue at all.
     */
    public function generate(string $month): string
    {
        $request = Request::create('/', 'GET', ['month' => $month]);

        // Deterministic path — NOT time-suffixed. Same month = same S3
        // key = overwritten in place on every run, not a new file.
        $path = "exports/pre-appointment-messages/pre-appointment-messages-{$month}.xlsx";

        Excel::store(new PreAppointmentMessagesPowerBiExport($request), $path, 's3');

        // Points at our own protected proxy route — NOT a raw S3 URL.
        // The S3 object itself can be fully private now; access is
        // controlled entirely by EnsureExportApiKey on this route.
        $downloadUrl = url("/api/power-bi-messages/download?month={$month}");

        PowerBiExportLink::updateOrCreate(
            ['month' => $month],
            [
                'download_url' => $downloadUrl,
                'generated_at' => now(),
            ]
        );



        return $downloadUrl;
    }
}
