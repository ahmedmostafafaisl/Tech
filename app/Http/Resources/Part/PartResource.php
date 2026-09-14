<?php

namespace App\Http\Resources\Part;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category_name' => $this->category ? $this->category->name : null,
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->warehouse ? $this->warehouse->name : null,
            'name' => $this->name,
            'description' => $this->description,
            'serial' => $this->serial,
            'code' => $this->code,
            'image' => $this->image,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'item_number' => $this->item_number,
            'type' => $this->type ?? 'part',
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
