<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Category;
use App\Models\Warehouse;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIds = Category::pluck('id')->all();
        $warehouseIds = Warehouse::pluck('id')->all();
        if (empty($categoryIds)) {
            // Seed some default categories if none exist
            \App\Models\Category::factory()->count(5)->create();
            $categoryIds = Category::pluck('id')->all();
        }

        for ($i = 1; $i <= 50; $i++) {
            $warranty = rand(0, 1);
            Item::create([
                'category_id' => $categoryIds[array_rand($categoryIds)],
                'warehouse_id'  => $warehouseIds[array_rand($warehouseIds)],
                'name' => 'Item ' . $i,
                'description' => 'Description for Item ' . $i,
                'serial' => strtoupper(Str::random(10)),
                'code' => 'ITM' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'image' => null,
                'price' => rand(10, 500),
                'quantity' => rand(1, 100),
                'warranty' => $warranty,
                'warranty_period' => $warranty ? rand(1, 5) : null,
            ]);
        }
    }
}
