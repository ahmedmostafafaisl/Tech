<?php

namespace App\Services\Sms;

use App\Models\Setting;
use App\Services\Auth\DefaultOtp;
use Illuminate\Support\Facades\Cache;


final class PhoneSmsRelayLimiter
{
    private function __construct(
        private readonly string $key,
        private readonly int $maxPerWindow,
        private readonly int $windowSeconds,
    ) {}

    public static function paymentLink(string $phone): self
    {
        return self::make('payment-link', $phone, 'sms_relay_payment_link_max_per_window', 'sms_relay_payment_link_window_minutes', 5, 60);
    }

    public static function invoice(string $phone): self
    {
        return self::make('invoice', $phone, 'sms_relay_invoice_max_per_window', 'sms_relay_invoice_window_minutes', 5, 60);
    }

    public static function whatsappNotify(string $phone): self
    {
        return self::make('whatsapp-notify', $phone, 'whatsapp_relay_notify_max_per_window', 'whatsapp_relay_notify_window_minutes', 5, 60);
    }

    /** Covers BOTH branches of sendPreAppointmentMessage (the normal flow and the lead_message flow): one phone, one quota. */
    public static function whatsappPreAppointment(string $phone): self
    {
        return self::make('whatsapp-pre-appointment', $phone, 'whatsapp_relay_pre_appointment_max_per_window', 'whatsapp_relay_pre_appointment_window_minutes', 5, 60);
    }

    private static function make(string $purpose, string $phone, string $maxSettingKey, string $windowSettingKey, int $defaultMax, int $defaultWindowMinutes): self
    {
        $bucket = DefaultOtp::normalizePhone($phone);
        $max    = max(1, (int) Setting::get($maxSettingKey, $defaultMax));
        $window = max(1, (int) Setting::get($windowSettingKey, $defaultWindowMinutes)) * 60;

        return new self("sms-relay:{$purpose}:{$bucket}", $max, $window);
    }

    /** True if recording $additional more sends now would push this phone over its limit for the window. */
    public function wouldExceed(int $additional = 1): bool
    {
        return (int) Cache::get($this->key, 0) + max(1, $additional) > $this->maxPerWindow;
    }

    /** Records $count sends against this phone's window and returns how many are on record now. */
    public function hit(int $count = 1): int
    {
        $count = max(1, $count);

        Cache::add($this->key, 0, $this->windowSeconds);

        return (int) Cache::increment($this->key, $count);
    }

    /** How many more sends this phone has left in the current window (never negative). */
    public function remaining(): int
    {
        return max(0, $this->maxPerWindow - (int) Cache::get($this->key, 0));
    }
}
