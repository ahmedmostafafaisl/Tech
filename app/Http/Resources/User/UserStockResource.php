<?php

namespace App\Http\Resources\User;

use App\Http\Resources\Item\ItemResource;
use App\Http\Resources\Part\PartResource;
use Illuminate\Http\Resources\Json\JsonResource;

// class UserStockResource extends JsonResource



use App\Models\Category;

class UserStockResource extends JsonResource
{
    public function toArray($request)
    {
        $items = $this->items ?? collect();
        $parts = $this->parts ?? collect();
        // Group items by category_id
        $itemsGrouped = $items
            ->filter(fn($userStockItem) => $userStockItem->item && $userStockItem->item->category)
            ->groupBy(fn($userStockItem) => $userStockItem->item->category->id);

        // Group parts by category_id
        $partsGrouped = $parts
            ->filter(fn($userStockPart) => $userStockPart->part && $userStockPart->part->category)
            ->groupBy(fn($userStockPart) => $userStockPart->part->category->id);

        // Get all unique category IDs from both groups
        $categoryIds = collect($itemsGrouped->keys())
            ->merge($partsGrouped->keys())
            ->unique();

        // Map into combined structure
        $categories = $categoryIds->map(function ($categoryId) use ($itemsGrouped, $partsGrouped) {
            $category = Category::find($categoryId);
            if (!$category) {
                return null; // Skip if category doesn't exist
            }
            return [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'items' => collect($itemsGrouped->get($categoryId))->map(fn($userStockItem) => [
                    'quantity' => $userStockItem->quantity,
                    'item' => new ItemResource($userStockItem->item),
                ])->values(),
                'parts' => collect($partsGrouped->get($categoryId))->map(fn($userStockPart) => [
                    'quantity' => $userStockPart->quantity,
                    'part' => new PartResource($userStockPart->part),
                ])->values(),
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
