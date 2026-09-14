<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Services\DY365\DyService;
use App\Services\DynamicsService;
use Illuminate\Support\Facades\Log;
use App\Repositories\Interfaces\AppointmentRepositoryInterface;

class SyncAllAppointments extends Command
{
    protected $signature = 'all:sync-appointments';
    protected $description = 'Sync all appointments from Dynamics API';

    protected $dyService;
    protected $appointmentRepo;

    public function __construct(DyService $dyService, AppointmentRepositoryInterface $appointmentRepo)
    {
        parent::__construct();
        $this->dyService = $dyService;
        $this->appointmentRepo = $appointmentRepo;
    }

    public function handle(): int
    {
        $currentPage = 1;
        $pageSize = 70; // You can change this based on your API limits
        $totalSynced = 0;
        $fromDate = now()->subDay()->format('Y-m-d');
        $toDate   = now()->addDays(3)->format('Y-m-d');
        try {
            while (true) {
                $response = $this->dyService->getAppointments([
                    'currentPage' => $currentPage,
                    'pageSize' => $pageSize,
                    'fromDate'    => $fromDate,
                    'toDate'      => $toDate,
                ]);

                if (
                    empty($response['Data']['Appointments']) ||
                    !is_array($response['Data']['Appointments'])
                ) {
                    $this->info("No more appointments found. Stopping sync.");
                    break;
                }

                $appointments = $response['Data']['Appointments'];

                $this->appointmentRepo->newSyncAllTechnicianAppointments($appointments);
                $totalSynced += count($appointments);

                $this->info("Page {$currentPage} synced (" . count($appointments) . " appointments).");

                // If less than pageSize returned, we're done
                if (count($appointments) < $pageSize) {
                    break;
                }

                $currentPage++;
            }

            $this->info("✅ Total appointments synced: {$totalSynced}");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Sync failed: ' . $e->getMessage());
            $this->error('❌ Sync failed. Check logs.');
            return Command::FAILURE;
        }
    }
}
