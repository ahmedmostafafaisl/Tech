<?php

namespace App\Http\Resources\Transfer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SingleWarehouseTransferResource extends JsonResource
{


    public function toArray(Request $request): array
    {
        // Merge requested items
        $mergedItems = optional($this->requestedItems)->map(function ($requestedItem) {
            $transferred = optional($this->transferredItems)
                ->firstWhere('item_id', $requestedItem->item_id);

            return [
                'warehouse_transfer_id' => $requestedItem->warehouse_transfer_id,
                'type' => 'item',
                'id' => $requestedItem->item_id,
                'name' => optional($requestedItem->item)->name,
                'requested_quantity' => $requestedItem->quantity,

                'transferred_quantity' => $transferred?->quantity ?? 0,
            ];
        }) ?? collect();

        // Merge requested parts
        $mergedParts = optional($this->requestedParts)->map(function ($requestedPart) {
            $transferred = optional($this->transferredParts)
                ->firstWhere('part_id', $requestedPart->part_id);

            return [
                'warehouse_transfer_id' => $requestedPart->warehouse_transfer_id,
                'type' => 'part',
                'id' => $requestedPart->part_id,
                'name' => optional($requestedPart->part)->name,
                'requested_quantity' => $requestedPart->quantity,
                'transferred_quantity' => $transferred?->quantity ?? 0,
            ];
        }) ?? collect();

        $mergedData = $mergedItems->merge($mergedParts)->values();

        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'tech_id' => $this->tech_id,
            'type' => $this->type,
            'date' => $this->date,
            'status' => $this->status,
            'reference_id' => $this->reference_id,
            'items_and_parts' => $mergedData,
        ];
    }
}
