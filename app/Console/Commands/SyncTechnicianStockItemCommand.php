<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Services\DY365\DyService;
use App\Repositories\User\UserStockRepository;

class SyncTechnicianStockItemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Usage:
     * php artisan sync:technician-stock-item {tech_id} {--item=}
     */
    protected $signature = 'sync:technician-stock-item {tech_id} {--item=}';

    /**
     * The console command description.
     */
    protected $description = 'Synchronize technician stock directly without using the queue.';

    /**
     * Execute the console command.
     */
    public function handle(DyService $dyService, UserStockRepository $userStockRepository)
    {
        $techId     = $this->argument('tech_id');
        $itemNumber = $this->option('item');

        $users = User::where('tech_id', $techId)
            ->where('type', 'tech')
            ->orderBy('id')
            ->get();

        if ($users->isEmpty()) {
            $this->error("❌ No technicians found for tech_id: {$techId}");
            return;
        }

        $pageSize = 700;

        foreach ($users as $technician) {
            if (!$technician->warehouse_id) {
                $this->warn("⚠️ Technician {$technician->id} has no warehouse_id. Skipping...");
                continue;
            }

            $page = 1;
            $this->info("🔄 Syncing technician: {$technician->id} | Warehouse: {$technician->warehouse_id}");

            do {
                $payload = [
                    'warehouseId' => $technician->warehouse_id,
                    'currentPage' => $page,
                    'pageSize'    => $pageSize,
                    'itemNumber'  => $itemNumber ?? "",
                ];

                $response   = $dyService->getWarehouseStock($payload);
                $pagination = $response['Data'] ?? [];
                $products   = $pagination['Products'] ?? [];

                if (empty($products)) {
                    $this->warn("⚠️ No products found for technician {$technician->id} on page {$page}.");
                    break;
                }

                $userStockRepository->syncTechnicianStock($products, $technician->id);

                $this->info("✅ Synced " . count($products) . " products (Page {$page})");

                $currentPage = $pagination['CurrentPage'] ?? $page;
                $pagesTotal  = $pagination['PagesTotal'] ?? $page;

                $page++;
            } while ($currentPage < $pagesTotal);
        }

        $this->info('🎉 Technician stock sync completed successfully.');
    }
}
