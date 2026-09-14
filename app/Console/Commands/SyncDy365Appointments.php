<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\CompleteNote;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use App\Services\DY365\DyService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SyncDy365Appointments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Example: php artisan dy365:sync-appointments
     */
    protected $signature = 'dy365:complete-appointments';

    /**
     * The console command description.
     */
    protected $description = 'Sync all appointments for today and yesterday where dy_completed = 0 and dy365_status != Completed.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting daily DY365 appointment completion process...');

        // ✅ Get yesterday’s appointments that were completed but not synced
        $appointments = Appointment::whereDate('appointment_date', now()->subDay())
            ->whereIn('status', ['complete', 'Completed'])
            ->where('dy_completed', 0)
            ->where('dy365_status', '!=', 'Completed')
            ->get();

        if ($appointments->isEmpty()) {
            $this->info('No pending appointments found.');
            return 0;
        }

        $this->info("Found {$appointments->count()} pending appointments.");

        $completedCount = 0;
        $notCompletedCount = 0;

        foreach ($appointments as $appointment) {
            try {
                $payment = $appointment->appointment_payments?->first();
                $paymentMethod = $payment->payment_method ?? 'CASH';
                $paymentReference = $payment->payment_id
                    ?: ($payment->reference_id ?: null);

                $body = [
                    "_contract" => [
                        "worker" => $appointment->technician->tech_id ?? '',
                        "SalesOrderId" => $appointment->sales_order_id,
                        "BookId" => $appointment->book_id,
                        "salesLines" => [
                            [
                                "PaymentReference" => $paymentReference,
                                "TotalAmount" => $appointment->total_price,
                                "PaymentMethod" => $paymentMethod,
                            ]
                        ]
                    ]
                ];

                $response = app(DyService::class)->completeSuccessPaymentsV2($body);

                $data = is_array($response)
                    ? $response
                    : ($response instanceof \Illuminate\Support\Arrayable ? $response->toArray() : []);

                if (isset($data['Status']) && $data['Status'] === true) {
                    $appointment->update([
                        'dy_completed' => 1,
                        'dy365_status' => 'Completed',
                        'dy_response'  => json_encode($data),
                        'v2_flag' => 1,

                    ]);

                    $this->info("✅ Appointment #{$appointment->id} marked as Completed.");
                    $completedCount++;

                    /**
                     * ✅ Handle Complete Note & Attachments
                     */
                    $completeNote = CompleteNote::where('appointment_id', $appointment->id)
                        ->latest()
                        ->with('images')
                        ->first();

                    if ($completeNote) {
                        $attachmentUrls = [];

                        foreach ($completeNote->images as $image) {
                            if (!empty($image->image)) {
                                try {
                                    // Generate temporary signed S3 URL
                                    $attachmentUrls[] = Storage::disk('s3')->temporaryUrl(
                                        $image->image,
                                        now()->addMinutes(120)
                                    );
                                } catch (\Exception $e) {
                                    Log::warning("Failed to generate URL for image {$image->id}: " . $e->getMessage());
                                }
                            }
                        }

                        if (!empty($attachmentUrls) || !empty($completeNote->note)) {
                            $payload = [
                                "_contract" => [
                                    "BookId" => $appointment->book_id,
                                    "Note" => $completeNote->note ?? '',
                                    "AttachmnetsURLs" => $attachmentUrls,
                                ]
                            ];

                            try {
                                app(DyService::class)->completeAppointmentWithAttachments($payload);
                                $this->info("📎 Sent attachments for appointment #{$appointment->id}");
                            } catch (\Exception $e) {
                                Log::error("Failed to send attachments for appointment {$appointment->id}: " . $e->getMessage());
                                $this->warn("⚠️ Attachment send failed for appointment #{$appointment->id}");
                            }
                        }
                    }
                } else {
                    $this->warn("⚠️ Appointment #{$appointment->id} not completed. Response: " . json_encode($data));
                    $notCompletedCount++;
                }
            } catch (\Throwable $e) {
                Log::error("CompleteSuccessPayments failed for appointment {$appointment->id}: " . $e->getMessage());
                $this->error("❌ Error on appointment #{$appointment->id}: {$e->getMessage()}");
                $notCompletedCount++;
            }
        }

        // ✅ Summary Report
        $this->line('');
        $this->info('===== Summary =====');
        $this->info("✅ Completed Appointments: {$completedCount}");
        $this->info("⚠️ Not Completed Appointments: {$notCompletedCount}");
        $this->info('🏁 DY365 sync process finished.');

        return 0;
    }
}
