<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            Warehouse::create([
                'name' => 'Warehouse ' . $i,
                'description' => 'Description for Warehouse ' . $i,
            ]);
        }
    }
}
