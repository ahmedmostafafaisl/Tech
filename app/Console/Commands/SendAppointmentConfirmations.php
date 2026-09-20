<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use App\Services\WhatsApp\WhatsAppService;
use App\Services\WhatsApp\WhatsAppConfirmationService;
use App\Http\Controllers\Api\WhatsApp\WhatsAppController;
use App\Http\Requests\WhatsApp\SendWhatsAppMessageRequest;
use App\Http\Controllers\NotifyController; // adjust to your actual controller namespace

class SendAppointmentConfirmations extends Command
{
    protected $signature = 'appointments:send-confirmations';
    protected $description = 'Send WhatsApp confirmation messages for appointments happening in 2 days';

    public function handle(WhatsAppConfirmationService $whatsapp)
    {
        $filePath = storage_path('logs/appointments_confirmation_' . now()->format('Y_m_d_His') . '.txt');

        // Ensure file exists
        File::put($filePath, "Appointments Confirmation Log\nGenerated at: " . now() . "\n\n");

        $targetDate = Carbon::now()->addDays(2)->toDateString();

        $appointments = Appointment::whereDate('appointment_date', $targetDate)
            ->where('confirmation_status', 'pending')
            ->get();

        foreach ($appointments as $appointment) {
            $items = $appointment->lines->map(function ($line) {
                if ($line->type === 'item' && $line->item) {
                    return $line->item->name;
                }
                if ($line->type === 'part' && $line->part) {
                    return $line->part->name;
                }
                return null;
            })->filter()->first();


            $phone = $appointment->phone ?? $appointment->customer->phone;
            $name  = $appointment->customer->username;

            if (!$phone) {
                $msg = "⚠️ Skipped appointment #{$appointment->id} (no phone)";
                $this->warn($msg);
                Log::warning($msg);
                File::append($filePath, "Skipped appointment #{$appointment->id} (no phone)\n");
                continue;
            }

            $data = [
                'phone' => $phone,
                'name'  => $name,
                'type'  => ($appointment->type ?? 'Service'),
                'date'  => Carbon::parse($appointment->appointment_date)->format('Y-m-d'),
                'items' => $items ?: ' - ',
            ];

            // body parameters
            $bodyParameters = [
                ["type" => "text", "text" => $data['name']],
                ["type" => "text", "text" => $data['type']],
                ["type" => "text", "text" => $data['date']],
                ["type" => "text", "text" => $data['items']],
            ];

            // button parameters
            $buttonParameters = [
                ["payload" => "{$appointment->id}Yes"],
                ["payload" => "{$appointment->id}Reschedule"],
                ["payload" => "{$appointment->id}Not interested"],
            ];

            $this->logToFile("Appointment ID {$appointment->id} payload: " . json_encode([
                'phone'            => $data['phone'],
                'template'         => 'pre_appointment_action_v3',
                // 'pre_appointment_action_v4',
                'bodyParameters'   => $bodyParameters,
                'buttonParameters' => $buttonParameters,
                'lang'             => 'ar'
            ], JSON_PRETTY_PRINT));

            try {
                $response = $whatsapp->sendTemplateMessage(
                    $data['phone'],
                    'pre_appointment_action_v3',
                    // 'pre_appointment_action_v4',
                    $bodyParameters,
                    $buttonParameters,
                    "ar"
                );
                Log::info("WhatsApp API response for appointment ID {$appointment->id}: " . json_encode($response));
                if (isset($response['error'])) {
                    $this->logToFile("Failed to send confirmation for appointment ID {$appointment->id}: {$response['error']['message']}");
                } else {
                    $appointment->update(['confirmation_status' => 'sent']);
                    $this->logToFile("Confirmation sent for appointment ID {$appointment->id}");
                    $msg = "✅ Confirmation sent to appointment #{$appointment->id}";
                    $this->info($msg);
                    Log::info($msg);
                    File::append($filePath, "Confirmation sent to appointment #{$appointment->id}\n");
                }
            } catch (\Exception $e) {
                $this->logToFile("Exception for appointment ID {$appointment->id}: {$e->getMessage()}");
                $msg = "❌ Failed to send confirmation for appointment #{$appointment->id}";
                $this->error($msg);
                Log::error($msg);
                File::append($filePath, "Failed to send confirmation for appointment #{$appointment->id}\n");
            }
        }


        return Command::SUCCESS;
    }

    protected function logToFile(string $message)
    {
        $logFile = storage_path('logs/appointment_confirmations.txt');
        file_put_contents($logFile, now() . " - " . $message . PHP_EOL, FILE_APPEND);
    }
}
