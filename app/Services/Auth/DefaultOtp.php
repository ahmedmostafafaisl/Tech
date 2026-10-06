<?php

namespace App\Services\Auth;

use App\Models\Setting;


final class DefaultOtp
{
    public const KEY_ENABLED = 'otp_default_enabled';

    public const KEY_CODE = 'otp_default_code';

    public const KEY_PHONES = 'otp_default_phones';

    /** Every setting whose key starts with this is invisible to, and unchangeable through, /api/settings. */
    public const PROTECTED_PREFIX = 'otp_default_';

    public static function isProtectedKey(string $key): bool
    {
        return str_starts_with($key, self::PROTECTED_PREFIX);
    }

    /** The code to issue for this phone right now, or null when the default OTP does not apply. Fails closed. */
    public static function codeFor(?string $phone): ?string
    {
        try {
            return self::resolve(
                Setting::isActive(self::KEY_ENABLED),
                Setting::get(self::KEY_CODE),
                Setting::get(self::KEY_PHONES),
                $phone,
                app()->environment('production')
            );
        } catch (\Throwable $e) {
            return null;   // a settings problem must never turn the feature on
        }
    }

    /** Pure decision, no I/O. */
    public static function resolve(bool $enabled, mixed $code, mixed $phonesList, ?string $phone, bool $isProduction): ?string
    {
        if (! $enabled) {
            return null;
        }

        $code = trim((string) $code);

        if (! self::isValidCode($code)) {
            return null;
        }

        $allowed = self::parsePhones(is_string($phonesList) ? $phonesList : '');

        if ($allowed === []) {
            return null;
        }

        if (in_array('*', $allowed, true) && ! $isProduction) {
            return $code;
        }

        $normalized = self::normalizePhone($phone);

        if ($normalized === '') {
            return null;
        }

        return in_array($normalized, array_diff($allowed, ['*']), true) ? $code : null;
    }

    public static function isValidCode(string $code): bool
    {
        return preg_match('/^\d{4}$/', $code) === 1;
    }

    /**
     * The allowlist as normalised phone numbers (plus a literal "*" if present).
     *
     * @return array<int, string>
     */
    public static function parsePhones(string $list): array
    {
        $phones = [];

        foreach (self::tokens($list) as $token) {
            $normalized = $token === '*' ? '*' : self::normalizePhone($token);

            if ($normalized !== '') {
                $phones[] = $normalized;
            }
        }

        return array_values(array_unique($phones));
    }

    /**
     * The raw entries of an allowlist. Numbers are separated by commas, semicolons or new lines only, so a number
     * may contain spaces ("+966 50 123 4567"). Two numbers separated by just a space are one invalid entry, which
     * is rejected with a clear message when the allowlist is set.
     *
     * @return array<int, string>
     */
    public static function tokens(string $list): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/[,;\r\n]+/', $list) ?: []),
            fn(string $token) => $token !== ''
        ));
    }

    /** True for a plausible phone number or "*" — used to reject typos when the allowlist is set. */
    public static function isValidPhoneToken(string $token): bool
    {
        return $token === '*' || preg_match('/^\+?\d{9,15}$/', preg_replace('/[\s-]+/', '', $token)) === 1;
    }

    /** Saudi numbers written as +966565268773, 00966565268773, 966565268773 or 565268773 all become 0565268773. */
    public static function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return '';
        }

        foreach (['00966', '966'] as $prefix) {
            if (str_starts_with($digits, $prefix) && strlen($digits) - strlen($prefix) === 9) {
                $digits = substr($digits, strlen($prefix));
                break;
            }
        }

        if (strlen($digits) === 9 && $digits[0] === '5') {
            $digits = '0' . $digits;
        }

        return $digits;
    }
}
