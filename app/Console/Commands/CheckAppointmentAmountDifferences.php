<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DirectAppointment;
use Illuminate\Support\Facades\Log;

class CheckAppointmentAmountDifferences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * You can run it like: php artisan appointments:check-differences
     */
    protected $signature = 'appointments:check-differences';

    /**
     * The console command description.
     */
    protected $description = 'Check and list all appointments where required amount differs from total price.';

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



        $differences = [];
        $allAppointmentsData = [];

        foreach ($appointments as $sales_order_id) {
            $this->info("🚀 Processing appointment: {$sales_order_id}");

            try {
                // Get response from API
                $response = app('App\Http\Controllers\Api\NewDirectIntegrationController')
                    ->refSingleAppointment($sales_order_id);

                // Convert JsonResponse to array
                $singleAppointment = $response instanceof \Illuminate\Http\JsonResponse
                    ? $response->getData(true)
                    : $response;

                // Check for required_amount key
                if (!array_key_exists('required_amount', $singleAppointment)) {
                    $this->warn("⏭ Skipping {$sales_order_id} — 'required_amount' key not found");
                    continue;
                }

                // Get required_amount value
                $required_amount = (float) ($singleAppointment['required_amount'] ?? 0);

                // Fetch related appointment
                $directAppointment = DirectAppointment::where('sales_order_id', $sales_order_id)
                    ->latest('id')
                    ->first();

                if (!$directAppointment) {
                    $this->warn("⚠️ No DirectAppointment found for {$sales_order_id}");
                    continue;
                }

                // 🟡 Update flags before processing
                $directAppointment->update([
                    'complete_flag' => 0,
                    'dy_response'   => null,
                ]);

                // Compute amounts
                $discount = (float) ($directAppointment->discount ?? 0);
                $total_price = (float) ($directAppointment->total_price ?? 0);
                $expected_required = round($total_price + $discount, 2);
                $difference_value = round($required_amount - $expected_required, 2);

                // Store all appointment data
                $allAppointmentsData[] = [
                    'sales_order_id' => $sales_order_id,
                    'required_amount' => $required_amount,
                    'discount' => $discount,
                    'total_price' => $total_price,
                    'expected_required' => $expected_required,
                    'difference' => $difference_value,
                    'status' => ($difference_value == 0.0) ? '✅ Match' : '❌ Mismatch',
                ];

                // Log based on comparison result
                if ($difference_value == 0.0) {
                    $this->info("✅ Match: {$sales_order_id} | Required: {$required_amount} | Expected: {$expected_required}");
                } else {
                    $this->error("❌ Mismatch: {$sales_order_id} | Required: {$required_amount} | Expected: {$expected_required}");

                    // Save to mismatched list
                    $differences[] = [
                        'sales_order_id' => $sales_order_id,
                        'required_amount' => $required_amount,
                        'discount' => $discount,
                        'total_price' => $total_price,
                        'expected_required' => $expected_required,
                        'difference' => $difference_value,
                    ];
                }
            } catch (\Throwable $e) {
                $this->error("💥 Error processing {$sales_order_id}: " . $e->getMessage());
                Log::error("Error in sync command for {$sales_order_id}", ['exception' => $e]);
                continue;
            }

            $this->line(str_repeat('-', 80));
        }


        $this->newLine();
        $this->info("✅ Total mismatches found: " . count($differences));

        // 🟥 Table 1: Mismatched records
        if (count($differences)) {
            $this->table(
                ['Sales Order ID', 'Required', 'Discount', 'Total', 'Expected', 'Difference'],
                $differences
            );
        }

        // 🟩 Table 2: All appointments summary
        $this->newLine();
        $this->info("📋 Summary of all processed appointments: " . count($allAppointmentsData));
        $this->table(
            ['Sales Order ID', 'Required', 'Discount', 'Total', 'Expected', 'Difference', 'Status'],
            $allAppointmentsData
        );

        return Command::SUCCESS;
    }
}
