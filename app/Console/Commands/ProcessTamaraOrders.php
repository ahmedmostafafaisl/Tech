<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\Payment\TamaraService;
use App\Models\DirectAppointmentPayment;

class ProcessTamaraOrders extends Command
{
    protected $signature = 'tamara:process-orders {file}';
    protected $description = 'Process Tamara orders from Excel file';

    protected $appointmentsList = [];

    public function handle()
    {
        $filePath = $this->argument('file');

        $this->info("📄 Importing file: $filePath");

        $importer = new \App\Imports\TamaraOrdersImport;

        Excel::import($importer, $filePath);

        $this->info("Done.");
        $this->info("Total appointments found: " . count($importer->appointmentsList));

        return 0;
    }


    private function processRow(array $row)
    {
        $order_id      = $row["id_order"] ?? null;
        $reference_id  = $row["order_reference_id"] ?? null;
        $total_amount  = $row["total_amount"] ?? null;
        $installments  = $row["instalments"] ?? null;

        if (!$reference_id) {
            $this->warn("⚠️ Missing reference_id — skipping row");
            return null;   // this is the correct equivalent of "continue"
        }

        // Fetch payment record
        $payment = DirectAppointmentPayment::where('reference_id', $reference_id)->first();

        if (!$payment) {
            $this->warn("❌ No payment found for reference: $reference_id");
            return null;   // this is the correct equivalent of "continue"
        }

        $appointment = $payment->directAppointment;
        $this->appointmentsList[] = $appointment;

        $tamara = app(TamaraService::class);

        try {
            $captureResponse = null; // <--- FIX: define variable first

            // Call Tamara API for this order
            $statusResponse = app(TamaraService::class)->getOrderStatus($order_id);
            Log::info('Tamara Status Response: ' . json_encode($statusResponse));
            if (isset($statusResponse['status'])) {
                if ($statusResponse['status'] === 'approved') {
                    $authResponse = app(TamaraService::class)->authorizeOrder($order_id);

                    if (isset($authResponse['status']) && $authResponse['status'] === 'authorised') {
                        $captureResponse = app(TamaraService::class)->captureOrderNew($reference_id, $order_id);
                    }
                } elseif ($statusResponse['status'] === 'authorised') {
                    $captureResponse = app(TamaraService::class)->captureOrderNew($reference_id, $order_id);
                }

                // Always return something safely
                return $captureResponse ?? [
                    'message' => 'Order status processed but no capture occurred.',
                    'status' => $statusResponse['status']
                ];
            }
        } catch (\Throwable $e) {
            Log::error('getAppointmentBySalesOrder failed: ' . $e->getMessage());
        }
    }
}
