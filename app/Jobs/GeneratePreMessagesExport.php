<?php

namespace App\Jobs;

use App\Exports\PreAppointmentMessagesPowerBiExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Runs the actual xlsx generation + S3 upload in the background, off the
 * HTTP request thread entirely — this is what fixes the 504, since the
 * request that triggers this returns immediately regardless of how long
 * the export itself takes.
 */
class GeneratePreMessagesExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly array $filters,
        private readonly string $jobToken,
    ) {}

    public function handle(): void
    {
        // Rebuild a Request from the stored filters (a Request object
        // itself isn't safely queueable/serializable).
        $request = Request::create('/', 'GET', $this->filters);

        $path = 'exports/pre-appointment-messages/pre-appointment-messages-' . now()->format('Y-m-d_His') . '.xlsx';

        try {
            Excel::store(new PreAppointmentMessagesPowerBiExport($request), $path, 's3');

            $expiresAt = now()->addMinutes(60);
            $downloadUrl = Storage::disk('s3')->temporaryUrl($path, $expiresAt);

            Cache::put("export_status:{$this->jobToken}", [
                'status'       => 'ready',
                'download_url' => $downloadUrl,
                'expires_at'   => $expiresAt->toIso8601String(),
            ], now()->addHours(2));
        } catch (\Throwable $e) {
            Log::error('GeneratePreMessagesExport failed', ['error' => $e->getMessage(), 'token' => $this->jobToken]);

            Cache::put("export_status:{$this->jobToken}", [
                'status'  => 'failed',
                'message' => 'Export generation failed.',
            ], now()->addHours(2));
        }
    }
}
