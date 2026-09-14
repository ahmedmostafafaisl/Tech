<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use App\Services\DY365\DyService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Repositories\User\UserStockRepository;

class SyncTechnicianStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $id;
    protected $itemNumber;

    public function __construct($id, $itemNumber = null)
    {
        $this->id = $id;
        $this->itemNumber = $itemNumber;
    }

    public function handle(DyService $dyService, UserStockRepository $userStockRepository)
    {
        $users = User::where('tech_id', $this->id)
            ->where('type', 'tech')
            ->orderBy('id')
            ->get();


        $pageSize = 700;

        foreach ($users as $technician) {
            if (!$technician->warehouse_id) {
                continue;
            }

            $page = 1;

            do {
                $payload = [
                    'warehouseId' => $technician->warehouse_id,
                    'currentPage' => $page,
                    'pageSize'    => $pageSize,
                    'itemNumber'   => $this->itemNumber ?? "",
                ];

                $response   = $dyService->getWarehouseStock($payload);
                $pagination = $response['Data'] ?? [];
                $products   = $pagination['Products'] ?? [];
                //
                $dirPath = storage_path('logs');
                $filePath = $dirPath . '/sync_stock.txt';



                if (empty($products)) {
                    break;
                }

                $userStockRepository->syncTechnicianStock($products, $technician->id);

                $currentPage = $pagination['CurrentPage'] ?? $page;
                $pagesTotal  = $pagination['PagesTotal'] ?? $page;
                $page++;
            } while ($currentPage < $pagesTotal);
        }
    }
}
