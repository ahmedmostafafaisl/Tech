<?php

namespace App\Console\Commands;

use App\Models\Warehouse;
use Illuminate\Console\Command;
use App\Services\DY365\DyService;
use App\Repositories\Warehouse\WarehouseRepository;

class SyncWarehouseStockItemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Example usage:
     * php artisan sync:warehouse-item-stock {warehouse_id} {--item=}
     */
    protected $signature = 'sync:warehouse-item-stock {warehouse_id} {--item=}';

    /**
     * The console command description.
     */
    protected $description = 'Synchronize warehouse stock directly without using the queue.';

    /**
     * Execute the console command.
     */
    public function handle(DyService $dynamicsService, WarehouseRepository $warehouseRepo)
    {
        $warehouseId = $this->argument('warehouse_id');
        $itemNumber  = $this->option('item');

        $warehouses = Warehouse::where('invent_location_id', $warehouseId)->get();

        if ($warehouses->isEmpty()) {
            $this->error("❌ No warehouses found for ID: {$warehouseId}");
            return;
        }

        foreach ($warehouses as $warehouse) {
            if (!$warehouse->rec_id) {
                $this->warn("⚠️ Warehouse {$warehouse->invent_location_id} has no rec_id. Skipping...");
                continue;
            }

            $page = 1;
            $pageSize = 700;

            $this->info("🔄 Syncing warehouse: {$warehouse->invent_location_id} (Rec ID: {$warehouse->rec_id})");

            do {
                $payload = [
                    'warehouseId'  => $warehouse->invent_location_id,
                    'itemNumber'   => $itemNumber ?? "",
                    'currentPage'  => $page,
                    'pageSize'     => $pageSize,
                ];

                $response = $dynamicsService->getWarehouseStock($payload);

                $pagination = $response['Data'] ?? [];
                $products   = $pagination['Products'] ?? [];

                // Write to log file
                $dirPath = storage_path('logs');
                $filePath = $dirPath . '/sync_stock.txt';

                if (!is_dir($dirPath)) {
                    mkdir($dirPath, 0755, true);
                }

                $logData = "ItemNumber: {$payload['itemNumber']} | Warehouse: {$payload['warehouseId']} | Page: {$payload['currentPage']} | Products: " . json_encode($products) . "\n";
                file_put_contents($filePath, $logData, FILE_APPEND | LOCK_EX);

                if (empty($products)) {
                    $this->warn("⚠️ No products found on page {$page}. Stopping sync for this warehouse.");
                    break;
                }

                $warehouseRepo->syncWarehouseStock($products);
                $this->info("✅ Synced " . count($products) . " products (Page {$page})");

                $currentPage = $pagination['CurrentPage'] ?? $page;
                $pagesTotal  = $pagination['PagesTotal'] ?? $page;

                $page++;
            } while ($currentPage < $pagesTotal);
        }

        $this->info('🎉 Sync process completed successfully.');
    }
}
