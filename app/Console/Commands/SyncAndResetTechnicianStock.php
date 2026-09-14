<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Services\DY365\DyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Repositories\User\UserStockRepository;

class SyncAndResetTechnicianStock extends Command
{
    protected $signature = 'sync:reset-technician-stock {phone}';
    protected $description = 'Delete technician stock by phone number and re-sync from Dynamics';

    protected $dyService;
    protected $userStockRepository;

    public function __construct(DyService $dyService, UserStockRepository $userStockRepository)
    {
        parent::__construct();
        $this->dyService = $dyService;
        $this->userStockRepository = $userStockRepository;
    }

    public function handle()
    {
        $phone = $this->argument('phone');

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            $this->error("❌ User with phone {$phone} not found.");
            return Command::FAILURE;
        }

        $this->info("🗑 Deleting all stocks for user {$user->id} ({$user->phone})...");

        DB::transaction(function () use ($user) {
            // User may have multiple stocks
            $stocks = $user->stock()->get();

            foreach ($stocks as $stock) {
                $stock->items()->delete();
                $stock->parts()->delete();
                $stock->delete();
            }
        });

        $this->info("✅ All stocks deleted for user {$user->id}. Starting sync...");

        $page = 1;
        $pageSize = 700;
        $totalSynced = 0;

        try {
            $retried = false;

            do {
                $this->info("➡ Fetching stock for technician {$user->id} — Page $page");

                $payload = [
                    'warehouseId' => $user->warehouse_id,
                    'itemNumber'  => "",
                    'currentPage' => $page,
                    'pageSize'    => $pageSize,
                ];

                $response   = $this->dyService->getWarehouseStock($payload);
                $pagination = $response['Data'] ?? [];
                $products   = $pagination['Products'] ?? [];

                // Lowercase all item_numbers before syncing
                $products = collect($products)->map(function ($p) {
                    if (isset($p['ItemNumber'])) {
                        $p['ItemNumber'] = strtolower($p['ItemNumber']);
                    }
                    return $p;
                })->toArray();

                if (empty($products)) {
                    $this->warn("⚠️ Empty products for technician {$user->id}, page $page.");

                    Log::warning("Warehouse stock empty", [
                        'technician_id' => $user->id,
                        'warehouseId'   => $user->warehouse_id,
                        'page'          => $page,
                        'retried'       => $retried,
                    ]);

                    if (!$retried) {
                        $this->warn("🔁 Retrying once...");
                        $retried = true;

                        $response   = $this->dyService->getWarehouseStock($payload);
                        $pagination = $response['Data'] ?? [];
                        $products   = $pagination['Products'] ?? [];

                        $products = collect($products)->map(function ($p) {
                            if (isset($p['ItemNumber'])) {
                                $p['ItemNumber'] = strtolower($p['ItemNumber']);
                            }
                            return $p;
                        })->toArray();
                    }

                    if (empty($products)) {
                        $this->warn("⚠️ Still empty after retry for technician {$user->id}, page $page.");
                        Log::warning("Retry returned empty products", [
                            'technician_id' => $user->id,
                            'page'          => $page,
                        ]);
                        break;
                    }
                }

                // Sync stock using repository
                $this->userStockRepository->newSyncTechnicianStock($products, $user->id);
                $totalSynced += count($products);

                $currentPage = $pagination['CurrentPage'] ?? $page;
                $pagesTotal  = $pagination['PagesTotal'] ?? $page;

                $this->info("📄 Page $currentPage of $pagesTotal processed for technician {$user->id}.");

                $page++;
            } while ($currentPage < $pagesTotal);

            $this->info("✅ Synced $totalSynced stock items for technician {$user->id} successfully.");
        } catch (\Exception $e) {
            $this->error("❌ Failed syncing technician {$user->id}: " . $e->getMessage());
            Log::error("Technician sync failed", [
                'technician_id' => $user->id,
                'warehouseId'   => $user->warehouse_id,
                'error'         => $e->getMessage(),
            ]);
        }

        return Command::SUCCESS;
    }
}
