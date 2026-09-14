<?php

namespace Database\Seeders;

use App\Models\Part;
use App\Models\Category;
use App\Models\Warehouse;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class PartSeeder extends Seeder
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
            Part::create([
                'category_id' => $categoryIds[array_rand($categoryIds)],
                'warehouse_id'  => $warehouseIds[array_rand($warehouseIds)],
                'name' => 'Part ' . $i,
                'description' => 'Description for Part ' . $i,
                'serial' => strtoupper(Str::random(10)),
                'code' => 'PRT' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'image' => null,
                'price' => rand(5, 200),
                'quantity' => rand(1, 50),
            ]);
        }
    }
}
