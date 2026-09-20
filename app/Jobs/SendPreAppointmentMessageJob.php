<?php

namespace App\Jobs;

use App\Models\PreAppointmentMessage;
use App\Services\WhatsApp\WhatsAppConfirmationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPreAppointmentMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60; // seconds between retries

    public function __construct(
        public PreAppointmentMessage $message,
        public array $bodyParameters,
        public array $buttonParameters,
    ) {}

    public function middleware(): array
    {
        return [new RateLimited('whatsapp')];
    }

    public function handle(WhatsAppConfirmationService $whatsapp): void
    {
        try {
            $response = $whatsapp->sendTemplateMessage(
                $this->message->phone,
                'pre_appointment_action_v3',
                // 'pre_appointment_action_v4',
                $this->bodyParameters,
                $this->buttonParameters,
                'ar'
            );

            $isSent = isset($response['messages'][0]['message_status']) &&
                $response['messages'][0]['message_status'] === 'accepted';

            $this->message->update([
                'is_sent'  => $isSent,
                'response' => json_encode($response),
            ]);

            Log::info('SendPreAppointmentMessageJob success', [
                'message_id' => $this->message->id,
                'phone'      => $this->message->phone,
                'is_sent'    => $isSent,
            ]);
        } catch (\Throwable $e) {
            $this->message->update([
                'is_sent'  => false,
                'response' => $e->getMessage(),
            ]);

            Log::error('SendPreAppointmentMessageJob failed', [
                'message_id' => $this->message->id,
                'phone'      => $this->message->phone,
                'attempt'    => $this->attempts(),
                'error'      => $e->getMessage(),
            ]);

            throw $e; // let queue retry
        }
    }

    public function failed(\Throwable $e): void
    {
        // called after all retries exhausted
        $this->message->update([
            'is_sent'  => false,
            'response' => 'Job failed after ' . $this->tries . ' attempts: ' . $e->getMessage(),
        ]);

        Log::error('SendPreAppointmentMessageJob permanently failed', [
            'message_id' => $this->message->id,
            'phone'      => $this->message->phone,
            'error'      => $e->getMessage(),
        ]);
    }
}
