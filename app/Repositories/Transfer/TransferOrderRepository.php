<?php

namespace App\Repositories\Transfer;

use Carbon\Carbon;
use App\Models\Item;
use App\Models\Part;
use App\Models\User;
use App\Models\UserStock;
use App\Models\Warehouse;
use Illuminate\Support\Arr;
use App\Models\TransferOrder;
use App\Models\UserStockItem;
use App\Models\UserStockPart;
use App\Services\DY365\DyService;
use App\Models\TransferOrderLines;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use App\Repositories\Interfaces\TransferOrderInterface;

class TransferOrderRepository implements TransferOrderInterface
{

    protected DyService $externalService;

    public function __construct(DyService $externalService)
    {
        $this->externalService = $externalService;
    }

    public function create(array $data)
    {
        return TransferOrder::create($data);
    }

    public function update($id, array $data)
    {
        $order = TransferOrder::findOrFail($id);
        $order->update($data);
        return $order;
    }

    public function delete(int $id): bool
    {
        return TransferOrder::where('id', $id)->delete() > 0;
    }


    public function find(int $id)
    {
        return TransferOrder::findOrFail($id);
    }
    public function getById(int $id)
    {
        return TransferOrder::findOrFail($id);
    }
    public function all()
    {
        return TransferOrder::all();
    }

    public function syncTransferOrder(array $transfers, int $technicianId): void
    {
        DB::beginTransaction();

        try {
            foreach ($transfers as $transfer) {
                // get warehouse  by $transfer['FromWarehouseRecId']
                $warehouse = Warehouse::where('rec_id', $transfer['FromWarehouseRecId'])->first();
                $transferOrder = TransferOrder::updateOrCreate(
                    [
                        'transfer_id' => $transfer['TransferId'],
                        // 'transfer_rec_id' => $transfer['TransferRecId']
                    ],
                    [
                        'transfer_rec_id' => $transfer['TransferRecId'],
                        'tech_id' => $technicianId,
                        'type' => empty($transfer['Type']) ? 'WarehouseToTechnician' : $transfer['Type'],
                        'warehouse_id' => $warehouse->id ?? $transfer['WarehouseId'],
                        'date' => now(),
                        'status' => $transfer['Status'],
                        'technician_status' => $transfer['TechnicianStatus'],
                        'from_warehouse' => $transfer['FromWarehouse'] ?? null,
                        'to_warehouse' => $transfer['ToWarehouse'] ?? null,
                        'from_warehouse_rec' => $transfer['FromWarehouseRecId'] ?? null,
                        'to_warehouse_rec' => $transfer['ToWarehouseRecId'] ?? null,
                    ]
                );

                // Insert lines if not already present
                if (!empty($transfer['TransferOrderLines']) && is_array($transfer['TransferOrderLines'])) {

                    // ✅ Delete existing TransferOrderLines for this transfer order
                    TransferOrderLines::where('transfer_order_id', $transferOrder->id)->delete();

                    foreach ($transfer['TransferOrderLines'] as $line) {
                        $itemNumber = $line['ItemId'] ?? null;
                        $recId = $line['Id'];
                        $type = null;

                        // Determine type and validate item/part
                        $item = Item::where('item_number', $itemNumber)->first();

                        if ($item) {
                            $type = 'item';
                        } else {
                            $part = Part::where('item_number', $itemNumber)->first();
                            if ($part) {
                                $type = 'part';
                            } else {
                                // type unknown/new
                                $type = 'new';
                            }
                        }

                        TransferOrderLines::updateOrCreate(
                            [
                                'transfer_order_id' => $transferOrder->id,
                                'item_rec_id' => $recId,
                            ],
                            [
                                'item_number' => $itemNumber,
                                'requested_quantity' => $line['RequestedQuantity'] ?? 0,
                                'transferred_quantity' => $line['TransferredQuantity'] ?? 0,
                                'type' => $type,
                            ]
                        );
                    }

                    /**
                     * ✅ If transfer is "Received" and has lines,
                     * trigger SyncTechnicianStockCommand for each item.
                     */
                    $user = User::where('id', $technicianId)->first();
                    // Use either tech_id or technician_rec_id for syncing
                    $techIdentifier = $user->tech_id ?? $user->technician_rec_id;
                    if (strtolower($transfer['Status']) === 'received') {
                        foreach ($transfer['TransferOrderLines'] as $line) {
                            if (!empty($line['ItemId'])) {
                                try {
                                    // Dispatch artisan command in background
                                    Artisan::queue('sync:technician-stock', [
                                        'tech_id' => $techIdentifier,
                                        '--itemNumber' => $line['ItemId'],
                                    ]);
                                } catch (\Throwable $e) {
                                    // Log the error and continue the loop
                                    Log::error('Failed to dispatch technician stock sync command', [
                                        'tech_id' => $techIdentifier,
                                        'itemNumber' => $line['ItemId'],
                                        'error' => $e->getMessage(),
                                    ]);

                                    continue; // Skip this iteration and continue with next line
                                }
                            }
                        }
                    }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Technician transfers sync failed: " . $e->getMessage());
        }
    }

    public function getTransfersByTechnician($technicianId)
    {
        return TransferOrder::with('lines')
            ->where('tech_id', $technicianId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function query()
    {
        return TransferOrder::query(); // Assuming your model is TransferOrder
    }

    //  new create
    public function createFromApiResponse(array $response): TransferOrder
    {
        return DB::transaction(function () use ($response) {

            $data = $response['Data'];
            $order = TransferOrder::create([
                'tech_id' => Auth()->id(),
                'transfer_rec_id' => $data['TransferRecId'],
                'transfer_id' => $data['TransferId'],
                'type' => $data['Type'],
                'status' => $data['Status'],
                'technician_status' => $data['TechnicianStatus'],
                'date' => Carbon::parse($data['CreationDate']),
                'from_warehouse' => $data['FromWarehouseId'],
                'to_warehouse' => $data['FromWarehouseId'],
                // flags
                'dy_synced' => 1,
                'dy_response' => json_encode($response),
            ]);

            foreach ($data['Items'] as $item) {
                $order->lines()->create([
                    'item_number' => $item['ItemId'],
                    'item_rec_id' => $item['ItemId'],
                    'requested_quantity' => $item['RequestedQuantity'],
                    'transferred_quantity' => $item['TransferredQuantity'],
                    'quantity' => $item['RequestedQuantity'],
                    'type' => 'item',
                ]);
            }

            return $order;
        });
    }

    // tech transfers
    public function mapTechnicianStatusFromDb(array $dyTransfers): array
    {

        // 1️⃣ Get transfer IDs from DY
        $transferIds = collect($dyTransfers)
            ->pluck('TransferId')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
        //  new
        return $dyTransfers;
        if (empty($transferIds)) {
            return $dyTransfers;
        }

        // 2️⃣ Get matching transfers from DB
        $dbTransfers = TransferOrder::whereIn('transfer_id', $transferIds)
            ->get(['transfer_id', 'technician_status'])
            ->keyBy('transfer_id');

        // 3️⃣ Map TechnicianStatus
        return collect($dyTransfers)->map(function ($transfer) use ($dbTransfers) {

            $transferId = $transfer['TransferId'] ?? null;

            if ($transferId && isset($dbTransfers[$transferId])) {
                $transfer['TechnicianStatus'] = $dbTransfers[$transferId]->technician_status;
            }

            return $transfer;
        })->values()->toArray();
    }

    public function mapSingleTechFromDb(array $transferResponse): array
    {
        $transferRecId = data_get($transferResponse, 'Data.TransferId');

        if (!$transferRecId) {
            return $transferResponse;
        }

        // check if transfer exists in DB
        $dbTransfer = TransferOrder::where('transfer_id', $transferRecId)
            ->select('technician_status')
            ->first();

        if (!$dbTransfer) {
            return $transferResponse; // not found → keep DY response
        }

        // ✅ override TechnicianStatus in the response with DB value
        $transferResponse['Data']['TechnicianStatus'] = $dbTransfer->technician_status;

        return $transferResponse;
    }
}
