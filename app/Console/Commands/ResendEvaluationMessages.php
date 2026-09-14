<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\Evaluation\EvaluationMessageController;
use App\Models\EvaluationMessage;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResendEvaluationMessages extends Command
{
    /**
     * php artisan whatsapp:resend-evaluation
     *     --from_date=2026-07-06 --to_date=2026-07-08 --sent=0
     *
     * All options are optional:
     *   --from_date / --to_date default to today if omitted
     *   --sent defaults to 0 (only resend messages that weren't confirmed sent)
     */
    protected $signature = 'whatsapp:resend-evaluation
                            {--from_date= : Start date (Y-m-d), defaults to today}
                            {--to_date= : End date (Y-m-d), defaults to today}
                            {--sent=0 : Only resend messages with this sent value (0 or 1)}';

    protected $description = 'Resend evaluation WhatsApp messages that were not sent within a date range';

    public function handle(EvaluationMessageController $controller)
    {
        $fromDate = $this->option('from_date')
            ? Carbon::parse($this->option('from_date'))->startOfDay()
            : Carbon::today()->startOfDay();

        $toDate = $this->option('to_date')
            ? Carbon::parse($this->option('to_date'))->endOfDay()
            : Carbon::today()->endOfDay();

        $sent = filter_var($this->option('sent'), FILTER_VALIDATE_BOOLEAN);

        $this->info("Fetching evaluation messages from {$fromDate->toDateString()} to {$toDate->toDateString()} where sent={$this->option('sent')}...");

        $messages = EvaluationMessage::where('sent', $sent)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get();

        if ($messages->isEmpty()) {
            $this->info('No messages found matching the given criteria.');
            return self::SUCCESS;
        }

        $this->info("Found {$messages->count()} message(s). Resending...");

        $sentCount = 0;
        $failedCount = 0;

        foreach ($messages as $message) {
            try {
                // Reuses the existing controller method as-is: it already
                // re-checks `sent`, re-derives the template, sends via
                // WhatsAppService, and updates the EvaluationMessage row —
                // no need to duplicate that logic here.
                $response = $controller->createAndSendEvaluation(
                    $message->phone,
                    $message->order_type,
                    $message->book_id
                );

                $payload = $response->getData(true);
                $isSent = data_get($payload, 'evaluation.sent') === true;

                Log::info('Resend evaluation message result', [
                    'message_id' => $message->id,
                    'book_id'    => $message->book_id,
                    'payload'    => $payload,
                ]);

                if ($isSent) {
                    $sentCount++;
                    $this->line("✔ Resent evaluation #{$message->id} ({$message->book_id}) to {$message->phone}");
                } else {
                    $failedCount++;
                    $this->warn("⚠ Evaluation #{$message->id} ({$message->book_id}) not confirmed sent: " . json_encode($payload));
                }
            } catch (\Throwable $e) {
                $failedCount++;

                Log::error('Failed to resend evaluation message', [
                    'message_id' => $message->id,
                    'book_id'    => $message->book_id,
                    'error'      => $e->getMessage(),
                ]);

                $this->error("✘ Failed #{$message->id} ({$message->book_id}): {$e->getMessage()}");
            }

            // Small delay so we don't hammer the WhatsApp API in a tight loop
            usleep(300000); // 300ms
        }

        $this->info("Done. Sent: {$sentCount}, Failed: {$failedCount}");

        return self::SUCCESS;
    }
}
