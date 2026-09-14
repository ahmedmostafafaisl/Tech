<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserStock;
use App\Models\Part;
use App\Models\UserStockPart;

class UserStockPartSeeder extends Seeder
{
    public function run(): void
    {
        $stocks = UserStock::all();
        $parts = Part::all();

        foreach ($stocks as $stock) {
            foreach ($parts->random(5) as $part) {
                // UserStockPart::create([
                //     'user_stock_id' => $stock->id,
                //     'part_id' => $part->id,
                //     'quantity' => rand(1, 15),
                //     'created_at' => now(),
                //     'updated_at' => now(),
                // ]);
            }
        }
    }
}
