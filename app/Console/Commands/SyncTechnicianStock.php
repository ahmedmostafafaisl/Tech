<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Services\DY365\DyService;
use App\Repositories\Interfaces\UserStockRepositoryInterface;

class SyncTechnicianStock extends Command
{
    protected $signature = 'technicians:sync-stock';
    protected $description = 'Sync technician stocks from DyService';

    protected DyService $dyService;
    protected UserStockRepositoryInterface $userStockRepository;

    public function __construct(DyService $dyService, UserStockRepositoryInterface $userStockRepository)
    {
        parent::__construct();
        $this->dyService = $dyService;
        $this->userStockRepository = $userStockRepository;
    }

    public function handle(): int
    {
        $this->info('🔄 Starting technician stock sync with pagination...');
        set_time_limit(0);

        $users = User::where('type', 'tech')
            ->orderBy('id')
            // ->orderBy('id', 'desc')
            ->get();

        if ($users->isEmpty()) {
            $this->warn('⚠️ No technicians found.');
            return Command::FAILURE;
        }

        foreach ($users as $technician) {
            if (!$technician->warehouse_id) {
                $this->warn("⚠️ Technician {$technician->id} missing warehouse_id.");
                continue;
            }

            $page = 1;
            $pageSize = 700; // You can adjust this
            $totalSynced = 0;

            try {
                do {
                    $this->info("➡ Fetching stock for technician {$technician->id} — Page $page");

                    $payload = [
                        'warehouseId'     => $technician->warehouse_id,
                        'itemNumber'   => "",
                        'currentPage' => $page,
                        'pageSize'   => $pageSize,
                    ];

                    $response = $this->dyService->getWarehouseStock($payload);

                    $pagination = $response['Data'] ?? [];
                    $products   = $pagination['Products'] ?? [];

                    if (empty($products)) {
                        $this->warn("⚠️ No products found for technician {$technician->id} on page $page.");
                        break;
                    }

                    $this->userStockRepository->syncTechnicianStock($products, $technician->id);
                    $totalSynced += count($products);

                    $currentPage = $pagination['CurrentPage'] ?? $page;
                    $pagesTotal  = $pagination['PagesTotal'] ?? $page;

                    $this->info("📄 Page $currentPage of $pagesTotal processed for technician {$technician->id}.");

                    $page++;
                } while ($currentPage < $pagesTotal);

                $this->info("✅ Synced $totalSynced stock items for technician {$technician->id} successfully.");
            } catch (\Exception $e) {
                $this->error("❌ Failed syncing technician {$technician->id}: " . $e->getMessage());
            }
        }

        $this->info('🏁 Technician stock sync completed.');
        return Command::SUCCESS;
    }
}
