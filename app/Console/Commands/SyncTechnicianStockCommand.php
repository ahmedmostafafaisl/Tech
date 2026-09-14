<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Services\DY365\DyService;
use Illuminate\Support\Facades\Log;
use App\Repositories\User\UserStockRepository;

class SyncTechnicianStockCommand extends Command
{
    /**
     * Command signature:
     * php artisan sync:technician-stock {tech_id} {--itemNumber=}
     */
    protected $signature = 'sync:technician-stock {tech_id} {--itemNumber=}';

    protected $description = 'Sync technician stock directly from Dynamics system without using a queued job.';

    protected $dyService;
    protected $userStockRepository;

    public function __construct(DyService $dyService, UserStockRepository $userStockRepository)
    {
        parent::__construct();
        $this->dyService = $dyService;
        $this->userStockRepository = $userStockRepository;
    }

    public function handle(): int
    {
        $techId = $this->argument('tech_id');
        $itemNumber = $this->option('itemNumber');

        $this->info("🔄 Starting direct technician stock sync for tech_id: {$techId}" . ($itemNumber ? " (item: {$itemNumber})" : ''));

        $users = User::where('tech_id', $techId)
            ->where('type', 'tech')
            ->orderBy('id')
            ->get();

        if ($users->isEmpty()) {
            $this->warn("⚠️ No technician found for tech_id: {$techId}");
            return Command::FAILURE;
        }

        $pageSize = 700;
        $totalSynced = 0;

        foreach ($users as $technician) {
            if (!$technician->warehouse_id) {
                $this->warn("⚠️ Technician {$technician->id} has no warehouse_id, skipping...");
                continue;
            }

            $this->info("➡️ Syncing warehouse: {$technician->warehouse_id} for technician: {$technician->id}");

            $page = 1;
            $hasMorePages = true;

            try {
                do {
                    $retried = false; // retry once if products are empty

                    $this->info("➡ Fetching stock for technician {$technician->id} — Page {$page}");

                    $payload = [
                        'warehouseId' => $technician->warehouse_id,
                        'currentPage' => $page,
                        'pageSize'    => $pageSize,
                        'itemNumber'  => $itemNumber ?? "",
                    ];

                    $response   = $this->dyService->getWarehouseStock($payload);
                    $pagination = $response['Data'] ?? [];
                    $products   = $pagination['Products'] ?? [];

                    // ✅ If empty, retry once
                    if (empty($products)) {
                        $this->warn("⚠️ Empty products for technician {$technician->id}, warehouse {$technician->warehouse_id}, page {$page}.");

                        Log::warning("Warehouse stock empty", [
                            'technician_id' => $technician->id,
                            'warehouseId'   => $technician->warehouse_id,
                            'page'          => $page,
                            'retried'       => $retried,
                        ]);

                        if (!$retried) {
                            $this->warn("🔁 Retrying once for technician {$technician->id}, warehouse {$technician->warehouse_id}...");
                            $retried = true;

                            // retry same page
                            $response   = $this->dyService->getWarehouseStock($payload);
                            $pagination = $response['Data'] ?? [];
                            $products   = $pagination['Products'] ?? [];
                        }

                        if (empty($products)) {
                            $this->warn("⚠️ Still empty after retry for technician {$technician->id}, warehouse {$technician->warehouse_id}, page {$page}.");
                            Log::warning("Retry also returned empty products", [
                                'technician_id' => $technician->id,
                                'warehouseId'   => $technician->warehouse_id,
                                'page'          => $page,
                            ]);
                            break; // stop paging this warehouse
                        }
                    }

                    // ✅ Sync products
                    $this->userStockRepository->syncTechnicianStock($products, $technician->id);
                    $totalSynced += count($products);

                    $currentPage = $pagination['CurrentPage'] ?? $page;
                    $pagesTotal  = $pagination['PagesTotal'] ?? $page;
                    $hasMorePages = $currentPage < $pagesTotal;
                    $page++;

                    $this->info("📦 Synced " . count($products) . " items (Page {$currentPage}/{$pagesTotal})");
                } while ($hasMorePages);

                $this->info("✅ Synced {$totalSynced} stock items for technician {$technician->id} successfully.");
            } catch (\Exception $e) {
                $this->error("❌ Failed syncing technician {$technician->id}: " . $e->getMessage());
                Log::error("Technician sync failed", [
                    'technician_id' => $technician->id,
                    'warehouseId'   => $technician->warehouse_id,
                    'error'         => $e->getMessage(),
                ]);
            }
        }

        $this->info("🏁 Sync completed. Total items synced: {$totalSynced}");
        return Command::SUCCESS;
    }
}
