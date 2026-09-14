<?php

namespace App\Http\Resources\Transfer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Warehouse\WarehouseResource;

class TransferOrderResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse' => $this->whenLoaded('warehouse'),
            'technician' => $this->whenLoaded('technician'),
            'type' => $this->type,
            'status' => $this->status,
            'technician_status' => $this->technician_status,
            'date' => $this->date,
            'transfer_rec_id' => $this->transfer_rec_id,
            'transfer_id' => $this->transfer_id,
            'from_warehouse' => $this->from_warehouse,
            'to_warehouse' => $this->to_warehouse,
            'from_warehouse_rec' => $this->from_warehouse_rec,
            'to_warehouse_rec' => $this->to_warehouse_rec,
            'created_at' => $this->created_at,
            'lines' => $this->whenLoaded('lines', function () {
                return $this->lines->map(function ($line) {
                    $name = null;

                    if ($line->type === 'item') {
                        $item = \App\Models\Item::where('item_number', $line->item_number)->first();
                        $name = $item?->name;
                    } elseif ($line->type === 'part') {
                        $part = \App\Models\Part::where('item_number', $line->item_number)->first();
                        $name = $part?->name;
                    }

                    // determine flag value
                    $flag = 'equal';
                    if ($line->transferred_quantity < $line->requested_quantity) {
                        $flag = 'lower';
                    } elseif ($line->transferred_quantity > $line->requested_quantity) {
                        $flag = 'upper';
                    }

                    return [
                        'id' => $line->id,
                        'item_number' => $line->item_number,
                        'quantity' => $line->quantity,
                        'type' => $line->type,
                        'requested_quantity' => $line->requested_quantity,
                        'transferred_quantity' => $line->transferred_quantity,
                        'name' => $name,
                        'flag' => $flag,
                    ];
                });
            }),


        ];
    }
}
