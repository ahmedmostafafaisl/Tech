<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DirectAppointment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesOrderPaymentExport;

class ImportSalesOrderPayments extends Command
{
    protected $signature = 'import:salesorder-payments {file}';
    protected $description = 'Import sales order file and export paid payments';

    public function handle()
    {
        $filePath = storage_path('app/v2.txt');

        if (!file_exists($filePath)) {
            $this->error("sales_orders.txt not found in storage/app/");
            return;
        }

        // Step 1: Read sales_orders.txt
        $salesOrders = collect(explode(PHP_EOL, file_get_contents($filePath)))
            ->filter()
            ->unique()
            ->values();

        $this->info("Loaded " . count($salesOrders) . " sales orders from sales_orders.txt");

        if ($salesOrders->isEmpty()) {
            $this->error("No sales orders found inside sales_orders.txt");
            return;
        }
        $salesOrders = $salesOrders->filter(function ($salesOrder) {
            return !empty(trim($salesOrder));
        })->values();

        // Step 2: Fetch direct appointments with paid payments
        $appointments = collect();

        $salesOrders->chunk(1000)->each(function ($chunk) use (&$appointments) {
            $chunkAppointments = DirectAppointment::with(['payments' => function ($q) {
                $q->where('status', 'paid');
            }])->whereIn('sales_order_id', $chunk)->get();

            $appointments->push(...$chunkAppointments);
        });



        if ($appointments->isEmpty()) {
            $this->error("No appointments found for the given sales orders.");
            return;
        }

        // Step 3: Convert data for export
        $data = $appointments->flatMap(function ($a) {
            return $a->payments->map(function ($p) use ($a) {
                return [
                    'sales_order'  => $a->sales_order_id,
                    'payment_type' => $p->payment_type,
                    'reference_id' => $p->reference_id,
                ];
            });
        });

        if ($data->isEmpty()) {
            $this->error("No paid payments found.");
            return;
        }

        // Step 4: Export Excel
        $fileName = 'paid_payments_export.xlsx';
        Excel::store(new SalesOrderPaymentExport($data), $fileName);

        $this->info("Export completed successfully: {$fileName}");
    }
}
