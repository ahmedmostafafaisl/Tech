<?php

namespace App\Http\Resources\Item;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Ramsey\Uuid\Type\Decimal;

class ItemResource extends JsonResource
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
            'total_user_stock_quantity' => $this->userStockItems()->sum('quantity'),
            'warranty' => $this->warranty,
            'warranty_period' => $this->warranty_period,
            'item_number' => $this->item_number,
            'type' => $this->type ?? 'item',
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
