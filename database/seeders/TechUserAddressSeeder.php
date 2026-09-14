<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Address;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TechUserAddressSeeder extends Seeder
{
    public function run(): void
    {
        $techUsers = User::where('type', 'customer')->get();

        foreach ($techUsers as $user) {
            Address::create([
                'user_id' => $user->id,
                'city' => 'Default City',
                'district' => 'Default District',
                'branch' => 'Main Branch',
                'sector' => 'Sector A',
                'status' => 'active',
                'type' => 'home',
                'name' => $user->name . "'s Address",
                'lat' => '25.2048',
                'long' => '55.2708',
                'location_note' => 'Auto-generated for tech user',
            ]);
        }
    }
}
