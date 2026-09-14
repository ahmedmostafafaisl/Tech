<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DY365\DyService;
use App\Services\DynamicsService;
use Illuminate\Support\Facades\Log;
use App\Repositories\Interfaces\WarehouseInterface;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use App\Repositories\Interfaces\WarehouseRepositoryInterface;

class SyncCategories extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'dynamics:sync-categories';

    /**
     * The console command description.
     */
    protected $description = 'Sync categories from Dynamics API and update local database';

    protected DyService $dynamicsService;
    protected CategoryRepositoryInterface $categoryRepository;

    /**
     * Inject services into command.
     */
    public function __construct(
        DyService $dynamicsService,
        CategoryRepositoryInterface $categoryRepository
    ) {
        parent::__construct();
        $this->dynamicsService = $dynamicsService;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting warehouse synchronization...');
        set_time_limit(0);

        try {
            $response = $this->dynamicsService->getProductCategories();

            if (isset($response['Data']['ProductCategories']) && is_array($response['Data']['ProductCategories'])) {
                $this->categoryRepository->syncCategories($response['Data']['ProductCategories']);
                $this->info('✅ Categories synced successfully.');
            } else {
                $this->warn('⚠️ No categories found to sync.');
                Log::warning('No categories found in Dynamics response.');
            }
        } catch (\Exception $e) {
            $this->error('❌ Failed to sync categories: ' . $e->getMessage());
            Log::error('Category sync error: ' . $e->getMessage());
        }
    }
}
