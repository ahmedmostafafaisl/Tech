<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DY365\DyService;
use App\Services\DynamicsService;
use Illuminate\Support\Facades\Log;
use App\Repositories\Interfaces\WarehouseInterface;
use App\Repositories\Interfaces\WarehouseRepositoryInterface;

class SyncWarehouses extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'dynamics:sync-warehouses';

    /**
     * The console command description.
     */
    protected $description = 'Sync warehouses from Dynamics API and update local database';

    protected DyService $dynamicsService;
    protected WarehouseInterface $warehouseRepository;

    /**
     * Inject services into command.
     */
    public function __construct(
        DyService $dynamicsService,
        WarehouseInterface $warehouseRepository
    ) {
        parent::__construct();
        $this->dynamicsService = $dynamicsService;
        $this->warehouseRepository = $warehouseRepository;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting warehouse synchronization...');
        set_time_limit(0);

        try {
            $response = $this->dynamicsService->getWarehouses();

            if (isset($response['Data']['Warehouses']) && is_array($response['Data']['Warehouses'])) {
                $this->warehouseRepository->syncWarehouses($response['Data']['Warehouses']);
                $this->info('✅ Warehouses synced successfully.');
            } else {
                $this->warn('⚠️ No warehouses found to sync.');
                Log::warning('No warehouses found in Dynamics response.');
            }
        } catch (\Exception $e) {
            $this->error('❌ Failed to sync warehouses: ' . $e->getMessage());
            Log::error('Warehouse sync error: ' . $e->getMessage());
        }
    }
}
