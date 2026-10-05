<?php

namespace App\Console\Commands;

use App\Models\PreAppointmentMessage;
use App\Services\DY365\DyService;
use App\Services\WhatsApp\CustomerResponseRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Sends ONE customer's WhatsApp button reply (confirm / reschedule / cancel) to
 * Dynamics as a change request.
 *
 * Launched in the background by WhatsAppController::receive(), the same way
 * payments:complete is, so the webhook can answer Meta immediately. That DY call
 * used to run inline with a 500s timeout and 3 retries: while DY was slow every
 * customer reply held a web worker for minutes, Meta re-sent the webhook, and
 * each re-send sent the change request again.
 *
 * Here it is a single, time-limited attempt (a change request is not idempotent,
 * so it is never retried), and the result is recorded on the message:
 *   flag = 1                       DY answered Status = true
 *   flag = 0, outcome answered     DY refused it
 *   flag = 0, outcome unconfirmed  sent, no usable answer — DY may have it (resend with care)
 *   flag = 0, outcome not_sent     DY never got it — safe to resend (dy:send-customer-responses)
 */
class SendAppointmentResponseToDyCommand extends Command
{
    protected $signature = 'whatsapp:send-appointment-response {id : PreAppointmentMessage id}';

    protected $description = "Send a customer's WhatsApp reply (confirm / reschedule / cancel) to Dynamics";

    /** Nobody is waiting on this process, but it must not hang forever either. */
    private const DY_TIMEOUT_SECONDS = 90;

    public function handle(): int
    {
        $id  = (int) $this->argument('id');
        $msg = PreAppointmentMessage::find($id);

        if (! $msg) {
            $this->warn("⚠️ PreAppointmentMessage {$id} not found");
            Log::channel('whatsapp')->warning('PreAppointmentMessage not found', ['id' => $id]);

            return self::FAILURE;
        }

        try {
            $body = CustomerResponseRequest::body($msg->book_id, $msg->sales_order, (string) $msg->customer_response);
        } catch (\InvalidArgumentException $e) {
            // 'pending' or an unknown value: there is no decision to send.
            $this->warn("⏭️ Nothing to send for message {$id}: {$e->getMessage()}");
            Log::channel('whatsapp')->info('Skipped sending to DY', [
                'pre_appointment_message_id' => $id,
                'customer_response'          => $msg->customer_response,
            ]);

            return self::SUCCESS;
        }

        Log::channel('whatsapp')->info('📤 Sending customer response to DY', [
            'pre_appointment_message_id' => $id,
            'request_body'               => $body,
        ]);

        $outcome = app(DyService::class)->submitCustomerChangeRequestWithin($body, self::DY_TIMEOUT_SECONDS);
        $record  = CustomerResponseRequest::record($outcome);

        // dy_response is a json column WITHOUT an array cast on the model, so it must be
        // encoded here (assigning the array directly is what the old inline code did).
        $msg->update([
            'flag'        => $record['flag'],
            'dy_response' => json_encode($record['dy_response'], JSON_UNESCAPED_UNICODE),
        ]);

        $context = [
            'pre_appointment_message_id' => $id,
            'outcome'                    => $record['dy_response']['outcome'],
            'dy_response'                => $record['dy_response'],
        ];

        if ($record['flag'] === 1) {
            Log::channel('whatsapp')->info('✅ DY accepted customer response', $context);
            $this->info("✅ Sent: message {$id}");

            return self::SUCCESS;
        }

        Log::channel('whatsapp')->error('❌ DY did not confirm customer response', $context);
        $this->error("❌ Not confirmed: message {$id} ({$record['dy_response']['outcome']})");

        return self::FAILURE;
    }
}
