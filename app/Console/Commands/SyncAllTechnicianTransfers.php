<?php

namespace App\Console\Commands;

use Exception;
use App\Models\User;
use Illuminate\Console\Command;
use App\Services\DY365\DyService;
use App\Repositories\Interfaces\TransferOrderInterface;
use App\Repositories\Contracts\TransferOrderRepositoryInterface;

class SyncAllTechnicianTransfers extends Command
{
    protected $signature = 'sync:technician-transfers';
    protected $description = 'Sync all technician transfer orders from the external API';

    protected DyService $dyService;
    protected TransferOrderInterface $transferOrderRepo;

    public function __construct(DyService $dyService, TransferOrderInterface $transferOrderRepo)
    {
        parent::__construct();
        $this->dyService = $dyService;
        $this->transferOrderRepo = $transferOrderRepo;
    }

    public function handle(): int
    {
        try {
            $users = User::where('type', 'tech')->get();

            if ($users->isEmpty()) {
                $this->warn('No technicians found.');
                return Command::FAILURE;
            }

            foreach ($users as $technician) {
                if (!$technician->technician_rec_id) {
                    $this->warn("Technician ID {$technician->id} missing 'tech_id'. Skipping...");
                    continue;
                }

                $payload = [
                    'worker' => $technician->technician_rec_id,
                ];

                $response = $this->dyService->getTechnicianTransfers($payload);

                if (isset($response['Data']['TransferOrders']) && is_array($response['Data']['TransferOrders']) && count($response['Data']['TransferOrders']) > 0) {
                    // $this->transferOrderRepo->syncTransferOrder($response['Data']['TransferOrders'], $technician->id);
                    $this->info("✅ Transfers synced successfully for Technician ID: {$technician->id}");
                } else {
                    $this->info("ℹ️ No transfers found for Technician ID: {$technician->id}");
                }
            }

            $this->info('All technician transfers sync completed.');
            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Failed to sync Transfers: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
