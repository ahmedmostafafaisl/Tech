<?php

namespace App\Services\Auth;

use App\Models\Setting;

/**
 * Adds phone numbers to the default-OTP allowlist (`otp_default_phones`), without touching the switch or the code.
 *
 * This is additive and idempotent: existing numbers are kept, duplicates are silently absorbed, and nothing here can
 * turn the feature on or off. `*` is refused — the wildcard (every account) is only ever set from the server
 * (`php artisan otp:default set-phones "*"`), never over an authenticated HTTP request.
 */
final class DefaultOtpAllowlist
{
    public const MAX_PHONES_PER_CALL = 1000;

    /**
     * @param  array<int, string>  $phones  raw tokens, as given (not yet normalised or validated)
     * @return array{added: array<int,string>, already_present: array<int,string>, allowlist: array<int,string>}
     *
     * @throws \InvalidArgumentException  one of the given tokens is not a usable phone number, or is "*"
     */
    public function add(array $phones): array
    {
        // Format/`*` are already rejected by AddDefaultOtpPhonesRequest; this stays as defence in depth for any
        // other caller of the service.
        foreach ($phones as $token) {
            $token = trim((string) $token);

            if ($token === '*') {
                throw new \InvalidArgumentException('"*" (every account) cannot be set through this endpoint; use the otp:default artisan command.');
            }

            if (! DefaultOtp::isValidPhoneToken($token)) {
                throw new \InvalidArgumentException("\"{$token}\" is not a valid phone number (9-15 digits, optional +).");
            }
        }

        $existing = DefaultOtp::parsePhones((string) Setting::get(DefaultOtp::KEY_PHONES));
        $incoming = array_values(array_unique(array_map([DefaultOtp::class, 'normalizePhone'], $phones)));

        $added = array_values(array_diff($incoming, $existing));
        $already = array_values(array_intersect($incoming, $existing));
        $merged = array_values(array_unique(array_merge($existing, $incoming)));

        if ($added !== []) {
            Setting::updateOrCreate(['key' => DefaultOtp::KEY_PHONES], ['value' => implode(',', $merged)]);
        }

        return ['added' => $added, 'already_present' => $already, 'allowlist' => $merged];
    }
}
