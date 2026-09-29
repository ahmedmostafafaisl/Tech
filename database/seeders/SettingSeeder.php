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

        // Global WhatsApp send kill-switch — default true (send normally).
        // firstOrCreate rather than updateOrCreate so re-running this
        // seeder never resets an operator's deliberate "false" back to
        // "true".
        Setting::firstOrCreate(
            ['key' => 'whatsapp_send_messages_active'],
            ['value' => 'true']
        );
    }
}
