<?php

namespace App\Console\Commands;

use App\Models\Warehouse;
use Illuminate\Console\Command;
use App\Services\DY365\DyService;
use Illuminate\Support\Facades\Log;
use App\Repositories\Interfaces\WarehouseInterface;

class SyncSingleWarehouseStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Usage:
     * php artisan sync:single-warehouse-stock W-0177
     */
    protected $signature = 'sync:single-warehouse-stock {warehouseId}';

    /**
     * The console command description.
     */
    protected $description = 'Sync stock for a specific warehouse only from Dynamics system';

    protected $dynamicsService;
    protected $warehouseRepo;

    public function __construct(DyService $dynamicsService, WarehouseInterface $warehouseRepo)
    {
        parent::__construct();
        $this->dynamicsService = $dynamicsService;
        $this->warehouseRepo   = $warehouseRepo;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $warehouseId = $this->argument('warehouseId');

        $this->info("🔄 Starting stock sync for warehouse: {$warehouseId}");
        set_time_limit(0);

        $warehouse = Warehouse::where('invent_location_id', $warehouseId)->first();

        if (!$warehouse) {
            $this->error("❌ Warehouse not found for ID: {$warehouseId}");
            return Command::FAILURE;
        }

        if (!$warehouse->rec_id) {
            $this->warn("⚠️ Warehouse {$warehouse->id} missing rec_id. Skipping sync.");
            return Command::FAILURE;
        }

        $page = 1;
        $pageSize = 700;
        $totalSynced = 0;

        try {
            $hasMorePages = true;

            while ($hasMorePages) {
                $retried = false; // Retry only once per page

                $this->info("➡ Fetching stock for warehouse {$warehouse->invent_location_id} — Page {$page}");

                $payload = [
                    'warehouseId' => $warehouse->invent_location_id,
                    'currentPage' => $page,
                    'pageSize'    => $pageSize,
                    'itemNumber'  => "",
                ];

                $response   = $this->dynamicsService->getWarehouseStock($payload);
                $pagination = $response['Data'] ?? [];
                $products   = $pagination['Products'] ?? [];

                // 🔁 Retry logic for empty products
                if (empty($products)) {
                    $this->warn("⚠️ Empty products for warehouse {$warehouse->invent_location_id}, page {$page}.");

                    Log::warning("Warehouse stock empty", [
                        'warehouse_id' => $warehouse->invent_location_id,
                        'page'         => $page,
                        'retried'      => $retried,
                    ]);

                    if (!$retried) {
                        $this->warn("🔁 Retrying once for warehouse {$warehouse->invent_location_id}...");
                        $retried = true;

                        // Retry the same page
                        $response   = $this->dynamicsService->getWarehouseStock($payload);
                        $pagination = $response['Data'] ?? [];
                        $products   = $pagination['Products'] ?? [];
                    }

                    if (empty($products)) {
                        $this->warn("⚠️ Still empty after retry for warehouse {$warehouse->invent_location_id}, page {$page}.");
                        Log::warning("Retry also returned empty products", [
                            'warehouse_id' => $warehouse->invent_location_id,
                            'page'         => $page,
                        ]);
                        break;
                    }
                }

                // ✅ Sync products
                $this->warehouseRepo->syncWarehouseStock($products);
                $count = count($products);
                $totalSynced += $count;

                $currentPage = $pagination['CurrentPage'] ?? $page;
                $pagesTotal  = $pagination['PagesTotal'] ?? $page;
                $hasMorePages = $currentPage < $pagesTotal;
                $page++;

                $this->info("📄 Page {$currentPage} of {$pagesTotal} processed for warehouse {$warehouse->invent_location_id}. Synced {$count} items.");
            }

            $this->info("✅ Synced {$totalSynced} stock items for warehouse {$warehouse->invent_location_id} successfully.");
        } catch (\Exception $e) {
            $this->error("❌ Failed syncing warehouse {$warehouse->invent_location_id}: " . $e->getMessage());

            Log::error("Warehouse sync failed", [
                'warehouse_id' => $warehouse->invent_location_id,
                'error'        => $e->getMessage(),
            ]);
        }

        $this->info("🏁 Single warehouse stock sync completed. Total synced: {$totalSynced}");
        return Command::SUCCESS;
    }
}
