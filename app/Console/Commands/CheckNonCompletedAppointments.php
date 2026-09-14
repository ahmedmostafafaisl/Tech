<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use Illuminate\Console\Command;
use App\Services\DY365\DyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckNonCompletedAppointments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:check-non-completed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check appointments from the last 3 days that are not completed in Dy365 and update their status if completed.';

    protected $dyService;

    /**
     * Create a new command instance.
     */
    public function __construct(DyService $dyService)
    {
        parent::__construct();
        $this->dyService = $dyService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startDate = '2025-10-10';
        $endDate = now()->format('Y-m-d');

        $this->info("🔍 Checking non-completed appointments for dates: {$startDate}, {$endDate}");

        $appointments = Appointment::whereBetween(DB::raw('DATE(appointment_date)'), [$startDate, $endDate])
            ->whereIn('status', ['processing', 'complete', 'completed'])
            ->where('dy365_status', '!=', 'Completed')
            ->get();

        if ($appointments->isEmpty()) {
            $this->info('✅ No pending appointments found.');
            return;
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($appointments as $appointment) {
            $body = [
                "salesOrderId" => $appointment->sales_order_id,
            ];

            try {
                $response = $this->dyService->getOrCreateInvoice($body);

                // Convert to array if object
                $responseArray = is_object($response) ? json_decode(json_encode($response), true) : $response;

                $status = $responseArray['status'] ?? $responseArray['Status'] ?? null;

                if ($status === 'success' || $status === true) {
                    $appointment->update([
                        'v2_flag'       => 1,
                        'dy_response'   => json_encode(['status' => 'success', 'response' => $responseArray]),
                        'dy_completed'  => 1,
                        'dy365_status'  => 'Completed',


                    ]);

                    $successCount++;
                    $this->info("✅ Appointment ID {$appointment->sales_order_id} marked as completed.");
                } else {
                    $appointment->update([
                        'dy_response' => json_encode(['status' => 'failed', 'response' => $responseArray]),
                    ]);

                    $this->error("❌ Failed for Appointment ID {$appointment->sales_order_id}");
                    Log::error("❌ Failed to complete payment for appointment ID: {$appointment->sales_order_id}", ['response' => $responseArray]);
                    $failCount++;
                }
            } catch (\Exception $e) {
                $this->error("⚠️ Exception for Appointment ID {$appointment->sales_order_id}");
                Log::error("⚠️ Exception for appointment ID {$appointment->sales_order_id}: " . $e->getMessage());
                $failCount++;
            }
        }

        $this->info("🎯 Process completed: {$successCount} success, {$failCount} failed.");
    }
}
