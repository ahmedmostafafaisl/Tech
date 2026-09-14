<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\DirectAppointment;
use Illuminate\Console\Command;
use App\Services\DY365\DyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendNonCompletedAppointmentsReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:send-non-completed-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders for today’s appointments that are not marked as completed in Dy365.';

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
        $appointments = [
            "SO-001202361",
            "SO-001212468",
            "SO-001268730",
            "SO-001321677",
            "SO-001321677",
            "SO-001321327",
            "SO-001331635",
            "SO-001311316",
            "SO-001330738",
            "SO-001336506",
            "SO-001334246",
            "SO-001340363",
            "SO-001326675",
            "SO-001326675"
        ];




        $rows = []; // to store summary for table

        foreach ($appointments as $appointment) {
            $newAppointment = DirectAppointment::where('sales_order_id', $appointment)
                ->orderBy('id', 'desc')
                ->first();

            if (!$newAppointment) {
                $this->warn("⚠️ Appointment not found for Sales Order ID: {$appointment}");
                continue;
            }

            $payments = $newAppointment->payments()
                ->where('status', 'paid')
                ->get();
            $totalPayments = $payments->sum('price');

            $SalesLines = $payments->map(function ($payment) {
                return [
                    'TotalAmount'   => floatval($payment->price ?? 0),
                    'PaymentMethod' => match ($payment->payment_type) {
                        'tabby'     => 'TABI',
                        'clickpay'  => 'E-Commerce',
                        default     => $payment->payment_type,
                    },
                ];
            })->values()->toArray();

            $body = [
                "_contract" => [
                    "worker"       => $newAppointment->tech_id,
                    "SalesOrderId" => $newAppointment->sales_order_id,
                    "BookId"       => $newAppointment->book_id,
                    "Discount"     => $newAppointment->discount ?? 0,
                    "SalesLines"   => $SalesLines,
                ],
            ];

            // Calculate values for table
            $required     = floatval($newAppointment->required_amount ?? 0);
            $discount     = floatval($newAppointment->discount ?? 0);
            $difference   = $required - ($totalPayments + $discount);

            // Save data for summary table
            $rows[] = [
                'Sales Order ID' => $newAppointment->sales_order_id,
                'Required'       => $required,
                'Discount'       => $discount,
                'Payments Total' => $totalPayments,
                'Difference'     => $difference,
                'Body Sent'      => json_encode($body['_contract']),
            ];

            // Optional: send to DY
            $newAppointment->update([
                'dy_body' => json_encode($body),
            ]);

            try {
                Log::info("📤 Sending CompleteSuccessPayments for Appointment ID {$newAppointment->id}: " . json_encode($body));
                $response = $this->dyService->completeSuccessPaymentsV2($body);
                $this->info("✅ Sent {$newAppointment->sales_order_id} successfully." . json_encode($body));
                Log::info("📤 Sending CompleteSuccessPayments for Appointment ID {$newAppointment->id}: " . json_encode($body));
            } catch (\Throwable $e) {
                Log::error("DY send failed for {$appointment}: " . $e->getMessage());
                $this->error("❌ Failed sending {$appointment}: " . $e->getMessage());
            }
        }

        // Print final table
        $this->table(
            ['Sales Order ID', 'Required', 'Discount', 'Payments Total', 'Difference', 'Body Sent'],
            $rows
        );

        $this->info('🎯 All non-completed appointment reminders processed successfully.');
    }
}
