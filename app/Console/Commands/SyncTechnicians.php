<?php

namespace App\Console\Commands;

use App\Repositories\Interfaces\TechRepositoryInterface;
use App\Services\DY365\DyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class SyncTechnicians extends Command
{
    protected $signature = 'dynamics:sync-technicians';
    protected $description = 'Sync technicians from Dynamics API';

    protected DyService $dynamicsService;
    protected TechRepositoryInterface $technicianRepository;

    public function __construct(
        DyService $dynamicsService,
        TechRepositoryInterface $technicianRepository
    ) {
        parent::__construct();
        $this->dynamicsService = $dynamicsService;
        $this->technicianRepository = $technicianRepository;
    }

    public function handle(): int
    {
        $this->info('🔄 Starting technician sync with pagination...');
        set_time_limit(0);

        $page = 1;
        $pageSize = 300; // adjustable
        $totalSynced = 0;

        try {
            do {
                $this->info("➡ Fetching technicians — Page $page");
                $payload = [
                    'currentPage' => $page,
                    'pageSize'    => $pageSize,
                ];
                $response = $this->dynamicsService->getTechnicians($payload);
                $pagination   = $response['data']['Data'] ?? [];
                $technicians  = $pagination['Technicians'] ?? [];
                if (empty($technicians)) {
                    $this->warn("⚠️ No technicians found on page $page.");
                    break;
                }
                // Sync technicians
                $this->technicianRepository->syncTechnicians($technicians);
                $totalSynced += count($technicians);
                $currentPage = $pagination['CurrentPage'] ?? $page;
                $pagesTotal  = $pagination['PagesTotal'] ?? $page;
                $this->info("📄 Page $currentPage of $pagesTotal processed.");
                $page++;
            } while ($currentPage < $pagesTotal);

            DB::statement("UPDATE users SET phone = TRIM(phone)");
            $this->info("✅ Synced $totalSynced technicians successfully.");
        } catch (\Exception $e) {
            $this->error('❌ Technician sync failed: ' . $e->getMessage());
            Log::error('Technician sync error: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info('🏁 Technician sync completed.');
        return Command::SUCCESS;
    }
}
