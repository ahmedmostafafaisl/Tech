<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\DirectAppointment;
use App\Services\DY365\DyService;

class SendAttachmentsToDynamicsCommand extends Command
{
    protected $signature = 'appointments:send-attachments {appointmentId}';
    protected $description = 'Send appointment attachments URLs to Dynamics in background';

    public function handle(DyService $dyService): int
    {
        $appointmentId = (int) $this->argument('appointmentId');

        // ✅ 1) Atomic lock: only one process can switch to "sending"
        $locked = DirectAppointment::where('id', $appointmentId)
            ->whereNotIn('dy_attachment_status', ['sending', 'done'])
            ->update(['dy_attachment_status' => 'sending']);

        if ($locked === 0) {
            $this->info("Already sending/done (or not found) for appointment {$appointmentId}");
            return Command::SUCCESS;
        }

        // ✅ 2) Re-fetch fresh row after locking
        $appointment = DirectAppointment::find($appointmentId);
        if (!$appointment) {
            // rare: deleted between update & find
            $this->error("❌ Appointment with ID {$appointmentId} not found after lock.");
            return Command::FAILURE;
        }

        $payloadJson = (string) ($appointment->dy_attachment_body ?? '');

        Log::info("📦 Retrieved payload for appointment {$appointmentId}", [
            'appointment_id' => $appointmentId,
            'payload_exists' => $payloadJson !== '',
        ]);

        // ✅ 3) Validate payload early; if invalid, mark failed (don’t leave it sending)
        if ($payloadJson === '') {
            $appointment->update([
                'dy_attachment_status'   => 'failed',
                'dy_attachment_response' => json_encode(['error' => 'dy_attachment_body is empty'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            ]);
            $this->error("❌ dy_attachment_body is empty for appointment {$appointmentId}");
            return Command::FAILURE;
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            $appointment->update([
                'dy_attachment_status'   => 'failed',
                'dy_attachment_response' => json_encode(['error' => 'Invalid JSON in dy_attachment_body'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            ]);
            $this->error("❌ Invalid JSON in dy_attachment_body for appointment {$appointmentId}");
            return Command::FAILURE;
        }

        try {
            Log::info("🚀 appointments:send-attachments started", [
                'appointment_id' => $appointmentId,
                'book_id'        => $appointment->book_id,
            ]);

            // ✅ 4) Send to Dynamics
            $response = $dyService->completeAppointmentWithAttachments($payload);

            $appointment->update([
                'dy_attachment_status'   => 'done',
                'dy_attachment_response' => json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            ]);

            $this->info("✅ Attachments sent successfully for appointment {$appointmentId}");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Log::error("❌ appointments:send-attachments failed", [
                'appointment_id' => $appointmentId,
                'error'          => $e->getMessage(),
            ]);

            $appointment->update([
                'dy_attachment_status'   => 'failed',
                'dy_attachment_response' => json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            ]);

            $this->error("❌ {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
