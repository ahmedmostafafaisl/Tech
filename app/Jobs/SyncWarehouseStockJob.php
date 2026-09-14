<?php

namespace App\Jobs;

use Exception;
use App\Models\Warehouse;
use App\Services\DY365\DyService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Warehouse\WarehouseRepository;

class SyncWarehouseStockJob implements \Illuminate\Contracts\Queue\ShouldQueue
{
    use \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels, \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue;

    protected $id;
    protected $itemNumber;

    public function __construct($id, $itemNumber = null)
    {
        $this->id = $id;
        $this->itemNumber = $itemNumber;
    }

    public function handle(DyService $dynamicsService, WarehouseRepository $warehouseRepo)
    {
        $warehouses = Warehouse::where('invent_location_id', $this->id)->get();

        foreach ($warehouses as $warehouse) {
            if (!$warehouse->rec_id) {
                continue;
            }

            $page = 1;
            $pageSize = 700;

            do {
                $payload = [
                    'warehouseId'  => $warehouse->invent_location_id,
                    'itemNumber'   => $this->itemNumber ?? "",
                    'currentPage'  => $page,
                    'pageSize'     => $pageSize,
                ];

                $response = $dynamicsService->getWarehouseStock($payload);

                $pagination = $response['Data'] ?? [];
                $products   = $pagination['Products'] ?? [];
                //
                $dirPath = storage_path('logs');
                $filePath = $dirPath . '/sync_stock.txt';

                // لو الفولدر مش موجود نعمله
                if (!is_dir($dirPath)) {
                    mkdir($dirPath, 0755, true);
                }

                // نجهز الـ log data
                $logData = "ItemNumber: {$payload['itemNumber']} | Warehouse: {$payload['warehouseId']} | Page: {$payload['currentPage']} | Products: " . json_encode($products) . "\n";

                // نكتب في الملف (لو مش موجود هيعمله)
                file_put_contents($filePath, $logData, FILE_APPEND | LOCK_EX);

                //
                if (empty($products)) {
                    break;
                }

                $warehouseRepo->syncWarehouseStock($products);

                $currentPage = $pagination['CurrentPage'] ?? $page;
                $pagesTotal  = $pagination['PagesTotal'] ?? $page;

                $page++;
            } while ($currentPage < $pagesTotal);
        }
    }
}
