<?php

namespace App\Http\Resources\Transfer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewTransferOrderResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transfer_rec_id' => $this->transfer_rec_id,
            'transfer_id' => $this->transfer_id,
            'tech_id' => $this->tech_id,
            'type' => $this->type,
            'from_warehouse' => $this->from_warehouse,
            'to_warehouse' => $this->to_warehouse,
            'status' => $this->status,
            'technician_status' => $this->technician_status,
            'date' => optional($this->date)->format('Y-m-d H:i:s'),
            'flag' => $this->flag,
            'dy_response' => $this->dy_response ? json_decode($this->dy_response) : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'lines'         => TransferOrderLineResource::collection($this->lines),

        ];
    }
}
