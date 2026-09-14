<?php

namespace App\Console\Commands;

use App\Models\PreAppointmentMessage;
use App\Services\WhatsApp\WhatsAppConfirmationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResendPreAppointmentMessages extends Command
{
    /**
     * php artisan whatsapp:resend-pre-appointment
     *     --from_date=2026-07-07 --to_date=2026-07-08 --is_sent=0
     *
     * All options are optional:
     *   --from_date / --to_date default to today if omitted
     *   --is_sent defaults to 0 (only resend messages that failed/weren't confirmed sent)
     */
    protected $signature = 'whatsapp:resend-pre-appointment
                            {--from_date= : Start date (Y-m-d), defaults to today}
                            {--to_date= : End date (Y-m-d), defaults to today}
                            {--is_sent=0 : Only resend messages with this is_sent value (0 or 1)}';

    protected $description = 'Resend pre-appointment WhatsApp confirmation messages that were not sent within a date range';

    public function handle(WhatsAppConfirmationService $whatsapp)
    {
        $fromDate = $this->option('from_date')
            ? Carbon::parse($this->option('from_date'))->startOfDay()
            : Carbon::today()->startOfDay();

        $toDate = $this->option('to_date')
            ? Carbon::parse($this->option('to_date'))->endOfDay()
            : Carbon::today()->endOfDay();

        $isSent = filter_var($this->option('is_sent'), FILTER_VALIDATE_BOOLEAN);

        $this->info("Fetching pre-appointment messages from {$fromDate->toDateString()} to {$toDate->toDateString()} where is_sent={$this->option('is_sent')}...");

        $messages = PreAppointmentMessage::where('is_sent', $isSent)
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
            $bodyParameters = [
                ["type" => "text", "text" => $message->name],
                ["type" => "text", "text" => $message->type ?? 'Service'],
                ["type" => "text", "text" => $message->date],
                ["type" => "text", "text" => $message->items ?? '-'],
            ];

            $buttonParameters = [
                ["payload" => "{$message->appointment_id}Yes"],
                // ["payload" => "{$message->appointment_id}Reschedule"],
                // ["payload" => "{$message->appointment_id}Not interested"],
            ];

            try {
                $response = $whatsapp->sendTemplateMessage(
                    $message->phone,
                    // 'pre_appointment_action_v3',
                    'pre_appointment_action_v4',
                    $bodyParameters,
                    $buttonParameters,
                    'ar'
                );

                Log::info('Resend pre-appointment message response', [
                    'message_id' => $message->id,
                    'response'   => $response->json(),
                ]);

                $isAccepted = isset($response['messages'][0]['message_status']) &&
                    $response['messages'][0]['message_status'] === 'accepted';

                $message->update([
                    'is_sent'  => $isAccepted,
                    'response' => json_encode($response->json()),
                ]);

                if ($isAccepted) {
                    $sentCount++;
                    $this->line("✔ Resent #{$message->id} to {$message->phone}");
                } else {
                    $failedCount++;
                    $this->warn("⚠ Message #{$message->id} to {$message->phone} was not accepted by WhatsApp");
                }
            } catch (\Throwable $e) {
                $failedCount++;

                $message->update([
                    'is_sent'  => false,
                    'response' => $e->getMessage(),
                ]);

                Log::error('Failed to resend WhatsApp pre-appointment message', [
                    'message_id' => $message->id,
                    'error'      => $e->getMessage(),
                ]);

                $this->error("✘ Failed #{$message->id}: {$e->getMessage()}");
            }

            // Small delay so we don't hammer the WhatsApp API in a tight loop
            usleep(300000); // 300ms
        }

        $this->info("Done. Sent: {$sentCount}, Failed: {$failedCount}");

        return self::SUCCESS;
    }
}
