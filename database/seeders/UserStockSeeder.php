<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserStock;

class UserStockSeeder extends Seeder
{
    public function run(): void
    {
        // Assuming you have at least one user
        $users = User::where('type', 'tech')->get();

        foreach ($users as $user) {
            UserStock::create([
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
