<?php

namespace App\Http\Resources\User;

use App\Http\Resources\Item\ItemResource;
use App\Http\Resources\Part\PartResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Category;
// class UserStockResource extends JsonResource





class SingleUserStockResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'items' => $this->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'item_id' => $item->item_id,
                    'quantity' => $item->quantity,
                    'name' => $item->item->name ?? null,
                    'description' => $item->item->description ?? null,
                    'price' => round((float) ($item->item->price ?? 0), 2),
                    'discount' => round((float) ($item->item->discount ?? 0), 2),
                    'discount_type' => $item->item->discount_type ?? 'fixed',

                    'serial' => $item->item->serial,
                    'code' => $item->item->code ?? null,
                    'warranty' => $item->item->warranty ?? null,
                    'warranty_period' => $item->item->warranty_period ?? null,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            }),

            'parts' => $this->parts->map(function ($part) {
                return [
                    'id' => $part->id,
                    'part_id' => $part->part_id,
                    'quantity' => $part->quantity,
                    'name' => $part->part->name ?? null,
                    'description' => $part->part->description ?? null,
                    'price' => round((float) ($part->part->price ?? 0), 2),
                    'discount' => round((float) ($part->part->discount ?? 0), 2),
                    'discount_type' => $part->part->discount_type ?? 'fixed',
                    'serial' => $part->part->serial,
                    'code' => $part->part->code ?? null,
                    'created_at' => $part->created_at,
                    'updated_at' => $part->updated_at,
                ];
            }),
        ];
    }
}
