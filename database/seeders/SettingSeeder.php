<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'new_required_amount_calculation_active'],
            ['value' => 'false']
        );
    }
}
