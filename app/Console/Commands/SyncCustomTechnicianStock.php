<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Services\DY365\DyService;
use App\Repositories\Interfaces\UserStockRepositoryInterface;

class SyncCustomTechnicianStock extends Command
{
    protected $signature = 'custom:tech-stock';
    protected $description = 'Sync custom technician stocks from DyService';

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

        $numbers = [
            /////////////////
            // '0543945253',
            // '0561453226',
            // '0561536319',
            // '0543915400',
            // '0561790508',
            // '0561735278',
            // '0562318524',
            // '0540818425',
            // '0560877924',
            // '0543915400',
            // '0569347996',
            // '0562492295',
            // '',
            // '',
            // '',
            // '0543945253', //311
            // '0540818425', //306
            // '0562492314', //5
            // '0561535853', //7
            // '0560471292',
            '0561535853',
            // '0561410689',
            // '0569347998',
            // '',
            // '',
            // '',
            // '',

        ];

        $users = User::where('type', 'tech')
            ->where('id', '>', 15)
            ->whereIn('phone', $numbers)
            ->orderBy('id', 'asc')
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

            $page        = 1;
            $pageSize    = 300;
            $totalSynced = 0;

            try {
                $retried = false; // retry only once

                do {
                    $this->info("➡ Fetching stock for technician {$technician->id} — Page $page");

                    $payload = [
                        'warehouseId' => $technician->warehouse_id,
                        'itemNumber'  => "",
                        'currentPage' => $page,
                        'pageSize'    => $pageSize,
                    ];

                    $response   = $this->dyService->getWarehouseStock($payload);
                    $pagination = $response['Data'] ?? [];
                    $products   = $pagination['Products'] ?? [];

                    if (empty($products)) {
                        $this->warn("⚠️ Empty products for technician {$technician->id}, warehouseId {$technician->warehouse_id}, page $page.");

                        \Log::warning("Warehouse stock empty", [
                            'technician_id' => $technician->id,
                            'warehouseId'   => $technician->warehouse_id,
                            'page'          => $page,
                            'retried'       => $retried,
                        ]);

                        if (!$retried) {
                            $this->warn("🔁 Retrying once for technician {$technician->id} (warehouseId {$technician->warehouse_id})...");
                            $retried = true;

                            // retry same page
                            $response   = $this->dyService->getWarehouseStock($payload);
                            $pagination = $response['Data'] ?? [];
                            $products   = $pagination['Products'] ?? [];
                        }

                        if (empty($products)) {
                            $this->warn("⚠️ Still empty after retry for technician {$technician->id}, warehouseId {$technician->warehouse_id}, page $page.");
                            \Log::warning("Retry also returned empty products", [
                                'technician_id' => $technician->id,
                                'warehouseId'   => $technician->warehouse_id,
                                'page'          => $page,
                            ]);
                            break;
                        }
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

                \Log::error("Technician sync failed", [
                    'technician_id' => $technician->id,
                    'warehouseId'   => $technician->warehouse_id,
                    'error'         => $e->getMessage(),
                ]);
            }
        }

        $this->info('🏁 Technician stock sync completed.');
        return Command::SUCCESS;
    }
}
