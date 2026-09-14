<?php

namespace App\Http\Resources\Tech;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockItemResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'item_id'     => $this->item_id,
            'quantity' => $this->quantity,
            'name' => $this->item->name,
            'description' => $this->item->description,
            'serial' => $this->item->serial,
            'image' => $this->item->image,
        ];
    }
}
