<?php

namespace App\Http\Resources\Transfer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseTransferResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'tech_id' => $this->tech_id,
            'date' => $this->date,
            'type' => $this->type,
            'status' => $this->status,
            'reference_id' => $this->reference_id,

        ];
    }
}
