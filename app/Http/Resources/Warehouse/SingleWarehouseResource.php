<?php


namespace App\Http\Resources\Warehouse;

use App\Http\Resources\Item\ItemResource;
use App\Http\Resources\Part\PartResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SingleWarehouseResource extends JsonResource
{
    protected $appointmentLines;

    public function __construct($resource, $appointmentLines = null)
    {
        parent::__construct($resource);
        $this->appointmentLines = $appointmentLines ?? collect();
    }

    public function toArray($request): array
    {
        // Collect appointment line item_numbers
        $appointmentItemNumbers = $this->appointmentLines
            ->pluck('item_number')
            ->filter()
            ->unique();

        // Items (exclude appointment ones)
        $items = $this->items
            ->where('quantity', '>', 0)
            ->reject(fn($item) => $appointmentItemNumbers->contains($item->item_number))
            ->map(function ($item) {
                return array_merge(
                    ['type' => 'item'],
                    (new ItemResource($item))->toArray(request())
                );
            })
            ->unique('item_number')   // ✅ remove duplicates
            ->values();
        // Parts (exclude appointment ones)
        $parts = $this->parts
            ->where('quantity', '>', 0)
            ->reject(fn($part) => $appointmentItemNumbers->contains($part->item_number))
            ->map(function ($part) {
                return array_merge(
                    ['type' => 'part'],
                    (new PartResource($part))->toArray(request())
                );
            })
            ->unique('item_number')   // ✅ remove duplicates
            ->values();

        // Merge items & parts
        $itemsAndParts = collect($items)
            ->merge(collect($parts))
            ->unique('item_number')
            ->values();
        // ✅ ensure no duplicates

        $warehouse['items_and_parts'] = $itemsAndParts;
        $warehouse['items'] = $items;
        $warehouse['parts'] = $parts;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'invent_location_id' => $this->invent_location_id,
            'type' => $this->type,
            'description' => $this->description,
            'warehouse' => $warehouse,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
