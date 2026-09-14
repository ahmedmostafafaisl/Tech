<?php

namespace App\Http\Resources\User;

use App\Models\Category;
use App\Http\Resources\Item\ItemResource;
use App\Http\Resources\Part\PartResource;
use Illuminate\Http\Resources\Json\JsonResource;

// class UserStockResource extends JsonResource




class MergedUserStockResource extends JsonResource
{
    protected $filter;

    public function __construct($resource, $filter = null)
    {
        parent::__construct($resource);
        $this->filter = $filter;
    }

    public function toArray($request)
    {
        if (!$this->resource) {
            return [
                'id' => null,
                'user_id' => null,
                'categories' => [],
                'created_at' => null,
                'updated_at' => null,
            ];
        }

        $itemsGrouped = collect();
        $partsGrouped = collect();

        // Apply filter to items or parts
        if (!$this->filter || $this->filter === 'item') {
            $itemsGrouped = $this->items
                ->filter(fn($userStockItem) => $userStockItem->item && $userStockItem->item->category)
                ->groupBy(fn($userStockItem) => $userStockItem->item->category->id);
        }

        if (!$this->filter || $this->filter === 'part') {
            $partsGrouped = $this->parts
                ->filter(fn($userStockPart) => $userStockPart->part && $userStockPart->part->category)
                ->groupBy(fn($userStockPart) => $userStockPart->part->category->id);
        }

        // Combine unique category IDs from both groups
        $categoryIds = collect($itemsGrouped->keys())
            ->merge($partsGrouped->keys())
            ->unique();

        // Grouped response
        $categories = $categoryIds->map(function ($categoryId) use ($itemsGrouped, $partsGrouped) {
            $category = Category::find($categoryId);

            $items = collect($itemsGrouped->get($categoryId))
                ->map(fn($userStockItem) => [
                    'type' => 'item',
                    'id' => $userStockItem->item->id,
                    'quantity' => $userStockItem->quantity,
                    'item_rec_id' => $userStockItem->item->rec_id,
                    'item_number' => $userStockItem->item->item_number,
                    'item_name' => $userStockItem->item->name,
                    'item_description' => $userStockItem->item->description,
                    'item_serial' => $userStockItem->item->serial,
                    'item_code' => $userStockItem->item->code,
                    'item_image' => $userStockItem->item->image,
                    'item_price' => $userStockItem->item->price,
                    'item_warranty' => $userStockItem->item->warranty,
                    'item_warranty_period' => $userStockItem->item->warranty_period,
                ])
                ->where('quantity', '>', 0)
                ->unique('item_number')
                ->values(); // Re-index collection


            $parts = collect($partsGrouped->get($categoryId))
                ->map(fn($userStockPart) => [
                    'type' => 'part',
                    'id' => $userStockPart->part->id,
                    'quantity' => $userStockPart->quantity,
                    'part_rec_id' => $userStockPart->part->rec_id,
                    'part_number' => $userStockPart->part->item_number,
                    'item_name' => $userStockPart->part->name,
                    'part_description' => $userStockPart->part->description,
                    'part_serial' => $userStockPart->part->serial,
                    'part_code' => $userStockPart->part->code,
                    'part_image' => $userStockPart->part->image,
                    'part_price' => $userStockPart->part->price,
                ])
                ->where('quantity', '>', 0)
                ->unique('part_number')
                ->values(); // Re-index collection


            return [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'items_and_parts' => $items->merge($parts)->values(),
            ];
        })->values();

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'categories' => $categories,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
