<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;


class CategorySeeder extends Seeder
{

    public function run(): void
    {
        for ($i = 1; $i <= 50; $i++) {
            Category::create([
                'name' => 'Category ' . $i,
                'description' => 'Description for Category ' . $i . ' - ' . Str::random(20),
            ]);
        }
    }
}
