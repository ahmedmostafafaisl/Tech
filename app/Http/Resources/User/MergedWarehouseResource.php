<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class MergedWarehouseResource extends JsonResource
{
    protected $appointmentLines;

    public function __construct($resource, $appointmentLines = null)
    {
        parent::__construct($resource);
        $this->appointmentLines = $appointmentLines ?? collect();
    }

    public function toArray($request)
    {
        // Collect item_numbers from appointment lines
        $appointmentItemNumbers = $this->appointmentLines
            ->pluck('item_number')
            ->filter()
            ->unique();

        // Items (exclude appointment ones)
        $items = collect($this->items ?? [])
            ->filter(fn($userStockItem) => $userStockItem->item)
            ->map(fn($userStockItem) => [
                'type' => 'item',
                'id' => $userStockItem->item->id,
                'category_id' => $userStockItem->item->category_id,
                'name' => $userStockItem->item->name,
                'description' => $userStockItem->item->description,
                'serial' => $userStockItem->item->serial,
                'code' => $userStockItem->item->code,
                'image' => $userStockItem->item->image,
                'price' => $userStockItem->item->price,
                'quantity' => $userStockItem->quantity,
                'item_number' => $userStockItem->item->item_number,
            ])
            ->filter(fn($item) => $item['quantity'] > 0)
            ->reject(fn($item) => $appointmentItemNumbers->contains($item['item_number']))
            ->unique('item_number')
            // ->unique('id')
            ->values();


        // Parts (exclude appointment ones)
        $parts = collect($this->parts ?? [])
            ->filter(fn($userStockPart) => $userStockPart->part)
            ->map(fn($userStockPart) => [
                'type' => 'part',
                'id' => $userStockPart->part->id,
                'category_id' => $userStockPart->part->category_id,
                'name' => $userStockPart->part->name,
                'description' => $userStockPart->part->description,
                'serial' => $userStockPart->part->serial,
                'code' => $userStockPart->part->code,
                'image' => $userStockPart->part->image,
                'price' => $userStockPart->part->price,
                'quantity' => $userStockPart->quantity,
                'item_number' => $userStockPart->part->item_number,
            ])
            ->filter(fn($part) => $part['quantity'] > 0)
            ->reject(fn($part) => $appointmentItemNumbers->contains($part['item_number']))
            ->unique('item_number')
            // ->unique('id')
            ->values();

        // Appointment lines mapping (unchanged)
        $appointmentLines = $this->appointmentLines->map(function ($line) {
            return [
                'type' => $line->line_type ?? 'item',
                'id' => $line->line_id,
                'item_number' => $line->item_number,
                'quantity' => $line->quantity,
                'price_before' => $line->warranty_status === 'Yes' ? 0 : $line->price,
                'price_after' => $line->warranty_status === 'Yes' ? 0 : ($line->price - ($line->discount ?? 0)),
                'total_price_before' => $line->warranty_status === 'Yes' ? 0 : ($line->total_amount + ($line->discount ?? 0) * $line->quantity),
                'total_price_after' => $line->warranty_status === 'Yes' ? 0 : $line->total_amount,
                'warranty_status' => $line->warranty_status,
            ];
        });

        // Merge all (without appointment lines inside items/parts)
        $itemsAndParts = $items->merge($parts)
            ->unique(fn($entry) => $entry['type'] . '_' . $entry['item_number'])
            ->values();
        if (!$this->resource) {
            return []; // or return some default
        }
        return [
            'id' => $this->id ?? null,
            'user_id' => $this->user_id ?? null,
            'warehouse' => [
                'items_and_parts' => $itemsAndParts ?? [],
                'items' => $items ?? [],
                'parts' => $parts ?? [],
                'appointment_lines' => $appointmentLines ?? [],
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s') ?? null,
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s') ?? null,
        ];
    }
}
