<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Services\DY365\DyService;
use App\Services\DynamicsService;
use Illuminate\Support\Facades\Log;
use App\Repositories\Interfaces\AppointmentRepositoryInterface;

class SyncTechnicianAppointments extends Command
{
    protected $signature = 'technicians:sync-appointments';
    protected $description = 'Sync all technician appointments from Dynamics API';

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
        $pageSize = 50; // adjust based on your API limit
        $fromDate = now()->subDay()->format('Y-m-d');
        $toDate   = now()->addDays(3)->format('Y-m-d');
        try {
            $users = User::where('type', 'tech')->get();

            if ($users->isEmpty()) {
                $this->warn('No technicians found.');
                return Command::FAILURE;
            }

            foreach ($users as $technician) {
                if (!$technician->technician_rec_id) {
                    Log::warning("Technician record ID not found for user ID {$technician->id}");
                    continue;
                }

                $currentPage = 1;
                $totalSynced = 0;

                while (true) {
                    $payload = [
                        'worker'      => $technician->technician_rec_id,
                        'currentPage' => $currentPage,
                        'pageSize'    => $pageSize,
                        'fromDate'    => $fromDate,
                        'toDate'      => $toDate,
                    ];

                    $response = $this->dyService->getTechnicianAppointments($payload);

                    if (
                        empty($response['Data']['Appointments']) ||
                        !is_array($response['Data']['Appointments'])
                    ) {
                        break; // no more appointments
                    }

                    $appointments = $response['Data']['Appointments'];

                    $this->appointmentRepo->syncAllTechnicianAppointments($appointments, $technician->id);
                    $totalSynced += count($appointments);

                    // Stop if less than pageSize returned
                    if (count($appointments) < $pageSize) {
                        break;
                    }

                    $currentPage++;
                }

                $this->info("Synced {$totalSynced} appointments for technician ID {$technician->id}");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Sync failed: ' . $e->getMessage());
            $this->error('Sync failed. Check logs.');
            return Command::FAILURE;
        }
    }
}
