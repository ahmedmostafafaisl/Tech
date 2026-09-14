<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserStock;
use App\Models\Item;
use App\Models\UserStockItem;

class UserStockItemSeeder extends Seeder
{
    public function run(): void
    {
        $stocks = UserStock::all();
        $items = Item::all();

        foreach ($stocks as $stock) {
            foreach ($items->random(5) as $item) {
                UserStockItem::create([
                    'user_stock_id' => $stock->id,
                    'item_id' => $item->id,
                    'quantity' => rand(1, 20),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
