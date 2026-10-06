<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        // Setting::updateOrCreate(
        //     ['key' => 'new_required_amount_calculation_active'],
        //     ['value' => 'false']
        // );

        // Global WhatsApp send kill-switch — default true (send normally).
        // firstOrCreate rather than updateOrCreate so re-running this
        // seeder never resets an operator's deliberate "false" back to
        // "true".
        // Setting::firstOrCreate(
        //     ['key' => 'whatsapp_send_messages_active'],
        //     ['value' => 'true']
        // );

        // Login hardening (Patch A). All OFF / empty until someone deliberately turns them on.
        // auth_require_otp_for_pin_login: also demand a fresh OTP for a new PIN login.
        Setting::firstOrCreate(['key' => 'auth_require_otp_for_pin_login'], ['value' => 'true']);

        // Default OTP for test / app-review accounts. Server-side only: managed with `php artisan otp:default`,
        // and hidden from the /api/settings endpoints.
        Setting::firstOrCreate(['key' => 'otp_default_enabled'], ['value' => 'true']);
        Setting::firstOrCreate(['key' => 'otp_default_code'], ['value' => '0102']);
        Setting::firstOrCreate(['key' => 'otp_default_phones'], ['value' => '0565268773,0561583554']);
    }
}
