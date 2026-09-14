<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Warehouse;
use App\Services\DY365\DyService;
use App\Repositories\Interfaces\WarehouseInterface;

class SyncCustomMainWarehouseStockCommand extends Command
{
    protected $signature = 'sync:custom-main-warehouse-stock';
    protected $description = 'Sync stock for Custom Main Warehouse only from Dynamics system';

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

        $warehouses_ids = [
            'M001',
            'M006',
            'B013',
            'M005',
            'B006',
            'B008',
            'B020',
            'B022',
        ];

        $warehouses = Warehouse::where('type', 'MainWarehouse')
            ->whereIn('invent_location_id', $warehouses_ids)
            ->orderBy('id', 'desc')
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

            $page        = 1;
            $pageSize    = 700; // adjustable
            $totalSynced = 0;

            try {
                $retried = false; // allow one retry per page

                do {
                    $this->info("➡ Fetching stock for warehouse {$warehouse->id} — Page $page");

                    $payload = [
                        'warehouseId'  => $warehouse->invent_location_id,
                        'currentPage'  => $page,
                        'pageSize'     => $pageSize,
                        'itemNumber'   => "",
                    ];

                    $response   = $this->dynamicsService->getWarehouseStock($payload);
                    $pagination = $response['Data'] ?? [];
                    $products   = $pagination['Products'] ?? [];

                    if (empty($products)) {
                        $this->warn("⚠️ Empty products for warehouse {$warehouse->id} — page $page");

                        \Log::warning("Warehouse stock empty", [
                            'warehouse_id' => $warehouse->id,
                            'invent_location_id' => $warehouse->invent_location_id,
                            'page' => $page,
                            'retried' => $retried,
                        ]);

                        if (!$retried) {
                            $retried = true;
                            $this->warn("🔁 Retrying once for warehouse {$warehouse->id} (invent_location_id {$warehouse->invent_location_id})...");

                            // Optional: small delay before retry to avoid API throttling
                            sleep(3);

                            // retry same page once
                            $response   = $this->dynamicsService->getWarehouseStock($payload);
                            $pagination = $response['Data'] ?? [];
                            $products   = $pagination['Products'] ?? [];
                        }

                        if (empty($products)) {
                            $this->warn("⚠️ Still empty after retry for warehouse {$warehouse->id}, page $page");
                            \Log::warning("Retry also returned empty products", [
                                'warehouse_id' => $warehouse->id,
                                'invent_location_id' => $warehouse->invent_location_id,
                                'page' => $page,
                            ]);
                            break;
                        }
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

                \Log::error("Warehouse sync failed", [
                    'warehouse_id' => $warehouse->id,
                    'invent_location_id' => $warehouse->invent_location_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info('🏁 Warehouse stock sync completed.');
        return Command::SUCCESS;
    }
}
