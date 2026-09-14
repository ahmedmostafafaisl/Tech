<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DY365\DyService;
use Illuminate\Support\Facades\Log;
use App\Repositories\Interfaces\TechRepositoryInterface;


class SyncCustomers extends Command
{
    protected $signature = 'dynamics:sync-customers';
    protected $description = 'Sync customers from Dynamics API';

    protected DyService $dynamicsService;
    protected TechRepositoryInterface $customerRepository;

    public function __construct(
        DyService $dynamicsService,
        TechRepositoryInterface $customerRepository
    ) {
        parent::__construct();
        $this->dynamicsService = $dynamicsService;
        $this->customerRepository = $customerRepository;
    }

    public function handle()
    {
        $this->info('🔄 Starting full customer sync with pagination...');
        set_time_limit(0);

        $page = 1;
        $pageSize = 1000;
        $totalSynced = 0;

        try {
            do {
                $this->info("➡ Fetching page: $page");

                $response = $this->dynamicsService->getCustomers([
                    'currentPage' => $page,
                    'pageSize' => $pageSize,
                ]);

                $pagination = $response['Data'] ?? [];
                $customers = $pagination['Customers'] ?? [];

                if (empty($customers)) {
                    $this->warn("⚠️ No customers found on page $page.");
                    break;
                }

                $this->customerRepository->syncCustomers($customers);
                $totalSynced += count($customers);

                $currentPage = $pagination['CurrentPage'] ?? $page;
                $pagesTotal = $pagination['PagesTotal'] ?? $page;

                $this->info("📄 Page $currentPage of $pagesTotal processed.");

                $page++;
            } while ($currentPage < $pagesTotal);

            $this->info("✅ Synced $totalSynced customers successfully.");
        } catch (\Exception $e) {
            $this->error('❌ Customer sync failed: ' . $e->getMessage());
            Log::error('Customer sync error: ' . $e->getMessage());
        }
    }
}
