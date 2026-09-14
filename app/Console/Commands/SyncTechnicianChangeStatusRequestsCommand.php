<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Services\DY365\DyService;
use App\Repositories\Interfaces\AppointmentChangeStatusRequestInterface;

class SyncTechnicianChangeStatusRequestsCommand extends Command
{
    protected $signature = 'sync:technician-status-requests';
    protected $description = 'Sync change status requests for all technicians';

    protected $dyService;
    protected $repo;

    public function __construct(DyService $dyService, AppointmentChangeStatusRequestInterface $repo)
    {
        parent::__construct();
        $this->dyService = $dyService;
        $this->repo = $repo;
    }

    public function handle()
    {
        $this->info('Starting technician change status request sync...');

        try {
            $users = User::where('type', 'tech')->get();

            if ($users->isEmpty()) {
                $this->warn('No technicians found.');
                return;
            }

            foreach ($users as $technician) {
                if (!$technician->technician_rec_id) {
                    $this->warn("Technician {$technician->id} has no technician_rec_id.");
                    continue;
                }

                $payload = ['worker' => $technician->technician_rec_id];
                $response = $this->dyService->getTechnicianChangeStatusRequests($payload);

                if (isset($response['Data']['Requests']) && is_array($response['Data']['Requests'])) {
                    $this->repo->syncAllTechnicianChangeStatusRequests($response['Data']['Requests']);
                    $this->info("Synced requests for technician ID {$technician->id}");
                } else {
                    $this->warn("No requests found for technician ID {$technician->id}");
                }
            }

            $this->info('Sync process completed successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to sync Requests: ' . $e->getMessage());
        }
    }
}
