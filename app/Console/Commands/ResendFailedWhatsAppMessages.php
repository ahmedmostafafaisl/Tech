<?php

namespace App\Console\Commands;

use App\Models\OrderLead;
use App\Models\PreAppointmentMessage;
use App\Services\Lead\LeadService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Resends any pre-appointment message or lead Q1 whose delivery_status/
 * q1_status is genuinely 'failed' — this is only possible now that the
 * WhatsApp webhook actually records delivery status (see
 * WhatsAppController::applyDeliveryStatus()); before that fix, 'failed'
 * was never recorded anywhere, so there was nothing to query for.
 *
 * php artisan whatsapp:resend-failed --from_date=2026-08-20 --to_date=2026-08-24
 */
class ResendFailedWhatsAppMessages extends Command
{
    protected $signature = 'whatsapp:resend-failed
                            {--from_date= : Start date (Y-m-d), defaults to 7 days ago}
                            {--to_date= : End date (Y-m-d), defaults to today}';

    protected $description = 'Resend pre-appointment messages and lead Q1 questions that WhatsApp confirmed as failed';

    public function handle(\App\Services\WhatsApp\WhatsAppConfirmationService $whatsapp, LeadService $leadService)
    {
        $fromDate = $this->option('from_date')
            ? Carbon::parse($this->option('from_date'))->startOfDay()
            : now()->subDays(7)->startOfDay();

        $toDate = $this->option('to_date')
            ? Carbon::parse($this->option('to_date'))->endOfDay()
            : now()->endOfDay();

        $this->resendPreAppointmentMessages($whatsapp, $fromDate, $toDate);
        $this->resendLeadQuestions($whatsapp, $leadService, $fromDate, $toDate);

        return self::SUCCESS;
    }

    private function resendPreAppointmentMessages($whatsapp, $fromDate, $toDate): void
    {
        $failed = PreAppointmentMessage::where('delivery_status', 'failed')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get();

        $this->info("Pre-appointment messages: {$failed->count()} failed in range.");

        foreach ($failed as $message) {
            $bodyParameters = [
                ["type" => "text", "text" => $message->name],
                ["type" => "text", "text" => $message->type ?? 'Service'],
                ["type" => "text", "text" => $message->date],
                ["type" => "text", "text" => $message->items ?? '-'],
            ];

            $buttonParameters = [
                ["payload" => "{$message->appointment_id}Yes"],
            ];

            try {
                $response = $whatsapp->sendTemplateMessage(
                    $message->phone,
                    'pre_appointment_action_v4',
                    $bodyParameters,
                    $buttonParameters,
                    'ar'
                );

                $isSent = isset($response['messages'][0]['message_status']) &&
                    $response['messages'][0]['message_status'] === 'accepted';

                $message->update([
                    'is_sent'         => $isSent,
                    'message_id'      => $response['messages'][0]['id'] ?? null,
                    'delivery_status' => null,
                    'delivery_error'  => null,
                    'response'        => json_encode($response),
                ]);

                $this->line($isSent ? "Resent #{$message->id}" : "Resend not accepted for #{$message->id}");
            } catch (\Throwable $e) {
                Log::error('Failed to resend pre-appointment message', [
                    'message_id' => $message->id,
                    'error'      => $e->getMessage(),
                ]);
                $this->error("Failed #{$message->id}: {$e->getMessage()}");
            }

            usleep(300000);
        }
    }

    private function resendLeadQuestions($whatsapp, LeadService $leadService, $fromDate, $toDate): void
    {
        $leadsQ1 = OrderLead::where('q1_status', 'failed')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get();

        $this->info("Lead Q1 messages: {$leadsQ1->count()} failed in range.");

        foreach ($leadsQ1 as $lead) {
            try {
                $q1 = $whatsapp->sendQuestion1(
                    $lead->mobile_number,
                    $lead->order_number,
                    $lead->customer_name ?? 'عميلنا العزيز',
                    $lead->product ?? 'استفسارك',
                );

                $response = $q1->json();
                $messageId = $leadService->extractMessageId($response);

                if ($messageId) {
                    $lead->update(['q1_message_id' => $messageId, 'q1_status' => null, 'q1_error' => null]);
                    $this->line("Resent Q1 for lead #{$lead->id}");
                } else {
                    $this->warn("Q1 resend not accepted for lead #{$lead->id}");
                }
            } catch (\Throwable $e) {
                Log::error('Failed to resend lead Q1', ['lead_id' => $lead->id, 'error' => $e->getMessage()]);
                $this->error("Failed lead #{$lead->id}: {$e->getMessage()}");
            }

            usleep(300000);
        }

        // Q2 intentionally not auto-resent here — it's normally sent as a
        // reply to the customer's Q1 answer, not on a fixed schedule.
        // Add a parallel block modeled on the Q1 one above if Q2 also
        // needs unconditional resending.
    }
}
