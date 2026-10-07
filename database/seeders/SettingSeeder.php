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
        // Setting::firstOrCreate(['key' => 'auth_require_otp_for_pin_login'], ['value' => 'true']);

        // Default OTP for test / app-review accounts. Server-side only: managed with `php artisan otp:default`,
        // and hidden from the /api/settings endpoints.
        // Setting::firstOrCreate(['key' => 'otp_default_enabled'], ['value' => 'true']);
        // Setting::firstOrCreate(['key' => 'otp_default_code'], ['value' => '0102']);
        // Setting::firstOrCreate(['key' => 'otp_default_phones'], ['value' => '0565268773,0561583554']);

        // SMS-relay abuse limits (PhoneSmsRelayLimiter). getPaymentLinks and sendInvoice are called directly by
        // Dynamics and cannot carry a shared secret, so they stay unauthenticated; this is the protection against
        // using them to text an arbitrary phone number. Default: 3 messages per phone per 60 minutes, per endpoint.
        Setting::firstOrCreate(['key' => 'sms_relay_payment_link_max_per_window'], ['value' => '5']);
        Setting::firstOrCreate(['key' => 'sms_relay_payment_link_window_minutes'], ['value' => '30']);
        Setting::firstOrCreate(['key' => 'sms_relay_invoice_max_per_window'], ['value' => '5']);
        Setting::firstOrCreate(['key' => 'sms_relay_invoice_window_minutes'], ['value' => '30']);

        // Same protection, for the two WhatsApp endpoints Dynamics calls directly (WhatsAppController::notify and
        // ::sendPreAppointmentMessage — the latter's lead_message branch shares the pre_appointment quota).
        Setting::firstOrCreate(['key' => 'whatsapp_relay_notify_max_per_window'], ['value' => '5']);
        Setting::firstOrCreate(['key' => 'whatsapp_relay_notify_window_minutes'], ['value' => '30']);
        Setting::firstOrCreate(['key' => 'whatsapp_relay_pre_appointment_max_per_window'], ['value' => '5']);
        Setting::firstOrCreate(['key' => 'whatsapp_relay_pre_appointment_window_minutes'], ['value' => '30']);
    }
}
