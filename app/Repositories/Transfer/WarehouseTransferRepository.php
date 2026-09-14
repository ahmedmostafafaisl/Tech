<?php


namespace App\Repositories\Transfer;

use Illuminate\Http\Request;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseRequestedItem;
use App\Models\WarehouseRequestedPart;
use App\Models\WarehouseTransferredItem;
use App\Models\WarehouseTransferredPart;
use App\Repositories\Interfaces\WarehouseTransferRepositoryInterface;



class WarehouseTransferRepository implements WarehouseTransferRepositoryInterface
{

    public function index()
    {
        return WarehouseTransfer::with(['requestedItems', 'requestedParts', 'transferredItems', 'transferredParts'])
            ->latest()
            ->get();
    }


    public function store(Request $request)
    {
        $transfer = WarehouseTransfer::create([
            'warehouse_id' => $request->warehouse_id,
            'tech_id' => auth()->id(),
            'date' => now(),
            'status' => 'pending',
            // 'reference_id' => $request->reference_id,
            'type' => $request->type ?? 'warehouse_to_tech',
        ]);

        foreach ($request->requested_items ?? [] as $item) {
            WarehouseRequestedItem::create([
                'warehouse_transfer_id' => $transfer->id,
                'item_id' => $item['item_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        foreach ($request->requested_parts ?? [] as $part) {
            WarehouseRequestedPart::create([
                'warehouse_transfer_id' => $transfer->id,
                'part_id' => $part['part_id'],
                'quantity' => $part['quantity'],
            ]);
        }

        return $transfer->load(['requestedItems.item', 'requestedParts.part']);
    }

    public function update(Request $request, int $id)
    {
        $transfer = WarehouseTransfer::findOrFail($id);

        if ($transfer->tech_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $transfer->update([
            'status' => $request->status ?? $transfer->status,
            'date' => $request->date ?? $transfer->date,
        ]);

        if ($request->has('transferred_items')) {
            // Clear old records (optional: keep if updating instead)
            WarehouseTransferredItem::where('warehouse_transfer_id', $transfer->id)->delete();
            foreach ($request->transferred_items as $item) {
                WarehouseTransferredItem::create([
                    'warehouse_transfer_id' => $transfer->id,
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                ]);
            }
        }

        if ($request->has('transferred_parts')) {
            WarehouseTransferredPart::where('warehouse_transfer_id', $transfer->id)->delete();
            foreach ($request->transferred_parts as $part) {
                WarehouseTransferredPart::create([
                    'warehouse_transfer_id' => $transfer->id,
                    'part_id' => $part['part_id'],
                    'quantity' => $part['quantity'],
                ]);
            }
        }

        return $transfer->load([
            'requestedItems.item',
            'requestedParts.part',
            'transferredItems.item',
            'transferredParts.part',
        ]);
    }

    public function find($id)
    {
        return WarehouseTransfer::with([
            'requestedItems',
            'requestedParts',
            'transferredItems',
            'transferredParts'
        ])->findOrFail($id);
    }

    public function delete($id)
    {
        $transfer = WarehouseTransfer::findOrFail($id);

        $transfer->requestedItems()->delete();
        $transfer->requestedParts()->delete();
        $transfer->transferredItems()->delete();
        $transfer->transferredParts()->delete();

        return $transfer->delete();
    }

    public function getRequestsForTech(int $techId, ?string $status = null)
    {
        $today = now()->toDateString();
        return WarehouseTransfer::with(['requestedItems.item', 'requestedParts.part'])
            ->where('tech_id', $techId)
            ->when($status === 'previous', function ($query) use ($today) {
                $query->whereDate('date', '<', $today);
            })
            ->when($status === 'today', function ($query) use ($today) {
                $query->whereDate('date', $today);
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
