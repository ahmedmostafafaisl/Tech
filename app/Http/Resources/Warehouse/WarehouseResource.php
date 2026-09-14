<?php


namespace App\Http\Resources\Warehouse;

use App\Http\Resources\Item\ItemResource;
use App\Http\Resources\Part\PartResource;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'invent_location_id' => $this->invent_location_id,
            'type' => $this->type,
            // 'items' => ItemResource::collection($this->items),
            // ->map(
            //     fn($userStockPart) => [
            //         'quantity' => $userStockPart->quantity,
            //         'part' => new PartResource($userStockPart->part)
            //     ]
            // ),
            // 'parts' => PartResource::collection($this->parts),
            // ->map(
            //     fn($userStockPart) => [
            //         'quantity' => $userStockPart->quantity,
            //         'part' => new PartResource($userStockPart->part)
            //     ]
            // ),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
