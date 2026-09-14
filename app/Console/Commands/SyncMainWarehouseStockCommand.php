<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Warehouse;
use App\Services\DY365\DyService;
use App\Repositories\Interfaces\WarehouseInterface;

class SyncMainWarehouseStockCommand extends Command
{
    protected $signature = 'sync:main-warehouse-stock';
    protected $description = 'Sync stock for Main Warehouse only from Dynamics system';

    protected $dynamicsService;
    protected $warehouseRepo;

    public function __construct(DyService $dynamicsService, WarehouseInterface $warehouseRepo)
    {
        parent::__construct();

        $this->dynamicsService = $dynamicsService;
        $this->warehouseRepo = $warehouseRepo;
    }

    public function handle(): int
    {
        $this->info('🔄 Starting main warehouse stock sync with pagination...');
        set_time_limit(0);

        $warehouses = Warehouse::where('type', 'MainWarehouse')
            // ->orderBy('id', 'desc')
            ->orderBy('id')
            ->get();

        if ($warehouses->isEmpty()) {
            $this->warn('⚠️ No warehouses found.');
            return Command::FAILURE;
        }

        foreach ($warehouses as $warehouse) {
            if (!$warehouse->rec_id) {
                $this->warn("⚠️ Warehouse ID {$warehouse->id} missing rec_id.");
                continue;
            }

            $page = 1;
            $pageSize = 700; // adjustable
            $totalSynced = 0;

            try {
                do {
                    $this->info("➡ Fetching stock for warehouse {$warehouse->id} — Page $page");

                    $payload = [
                        'warehouseId'  => $warehouse->invent_location_id,
                        'currentPage'  => $page,
                        'pageSize'     => $pageSize,
                        'itemNumber'   => "",
                    ];

                    $response = $this->dynamicsService->getWarehouseStock($payload);

                    $pagination = $response['Data'] ?? [];
                    $products   = $pagination['Products'] ?? [];

                    if (empty($products)) {
                        $this->warn("⚠️ No products found for warehouse {$warehouse->id} on page $page.");
                        break;
                    }

                    $this->warehouseRepo->syncWarehouseStock($products);
                    $totalSynced += count($products);

                    $currentPage = $pagination['CurrentPage'] ?? $page;
                    $pagesTotal  = $pagination['PagesTotal'] ?? $page;

                    $this->info("📄 Page $currentPage of $pagesTotal processed for warehouse {$warehouse->id}.");

                    $page++;
                } while ($currentPage < $pagesTotal);

                $this->info("✅ Synced $totalSynced stock items for warehouse {$warehouse->id} successfully.");
            } catch (\Exception $e) {
                $this->error("❌ Failed syncing warehouse {$warehouse->id}: " . $e->getMessage());
            }
        }

        $this->info('🏁 Warehouse stock sync completed.');
        return Command::SUCCESS;
    }
}
