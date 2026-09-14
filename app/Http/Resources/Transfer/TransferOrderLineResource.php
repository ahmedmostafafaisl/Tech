<?php

namespace App\Http\Resources\Transfer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferOrderLineResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'item_number'         => $this->item_number,
            'requested_quantity' => $this->requested_quantity,
            'transferred_quantity' => $this->transferred_quantity,
        ];
    }
}
