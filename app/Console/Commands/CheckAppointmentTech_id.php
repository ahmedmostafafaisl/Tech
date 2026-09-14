<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DirectAppointment;

class CheckAppointmentTech_id extends Command
{
    /**
     * The name and signature of the console command.
     *
     * You can run it like: php artisan appointments:check-differences
     */
    protected $signature = 'appointments:check-worker';

    /**
     * The console command description.
     */
    protected $description = 'Check and list all appointments where worker id   differs from  book id .';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $appointments  = DirectAppointment::whereColumn('tech_id', 'book_id')
            ->orWhereNull('tech_id')
            ->get();

        $differences = [];
        $allAppointmentsData = [];

        foreach ($appointments as $appointment) {
            $response = app('App\Http\Controllers\Api\NewDirectIntegrationController')
                ->refSingleAppointment($appointment->sales_order_id);

            // Convert JsonResponse to array
            $singleAppointment = $response instanceof \Illuminate\Http\JsonResponse
                ? $response->getData(true)
                : $response;

            // Skip if required_amount is missing (but allow 0.00)
            if (!array_key_exists('required_amount', $singleAppointment)) {
                $this->info("⏭ Skipping {$appointment->sales_order_id} — required_amount key not found");
                continue;
            }

            // ✅ Update tech_id from Worker if available
            if (!empty($singleAppointment['Worker'])) {
                $appointment->tech_id = $singleAppointment['Worker'];
                $appointment->save();

                $this->info("🔄 Updated tech_id for {$appointment->sales_order_id} to {$singleAppointment['Worker']}");
            } else {
                $this->warn("⚠️ Worker key missing for {$appointment->sales_order_id}");
            }

            $required_amount = (float) ($singleAppointment['required_amount'] ?? 0);
            $directAppointment = DirectAppointment::where('sales_order_id', $appointment->sales_order_id)
                ->latest('id')
                ->first();

            if (!$directAppointment) {
                $this->warn("⚠️ No DirectAppointment found for {$appointment->sales_order_id}");
                continue;
            }

            $discount = (float) ($directAppointment->discount ?? 0);
            $total_price = (float) ($directAppointment->total_price ?? 0);
            $expected_required = round($total_price + $discount, 2);
            $difference_value = round($required_amount - $expected_required, 2);

            $allAppointmentsData[] = [
                'sales_order_id' => $appointment->sales_order_id,
                'required_amount' => $required_amount,
                'discount' => $discount,
                'total_price' => $total_price,
                'expected_required' => $expected_required,
                'difference' => $difference_value,
                'status' => ($difference_value == 0.0) ? '✅ Match' : '❌ Mismatch',
            ];

            if ($difference_value != 0.0) {
                $differences[] = [
                    'sales_order_id' => $appointment->sales_order_id,
                    'required_amount' => $required_amount,
                    'discount' => $discount,
                    'total_price' => $total_price,
                    'expected_required' => $expected_required,
                    'difference' => $difference_value,
                ];

                $this->error("❌ Mismatch: {$appointment->sales_order_id} | Required: {$required_amount} | Expected: {$expected_required}");
            }
        }

        $this->newLine();
        $this->info("✅ Total mismatches found: " . count($differences));

        if (count($differences)) {
            $this->table(
                ['Sales Order ID', 'Required', 'Discount', 'Total', 'Expected', 'Difference'],
                $differences
            );
        }

        $this->newLine();
        $this->info("📋 Summary of all processed appointments: " . count($allAppointmentsData));
        $this->table(
            ['Sales Order ID', 'Required', 'Discount', 'Total', 'Expected', 'Difference', 'Status'],
            $allAppointmentsData
        );

        return Command::SUCCESS;
    }
}
