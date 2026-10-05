<?php

namespace App\Helper;

/**
 * created_at / updated_at as "Y-m-d H:i:s" in the application timezone (Asia/Riyadh)
 * — the same wall-clock value stored in the database — instead of Laravel's default JSON
 * form, which is ISO-8601 in UTC ("2026-10-05T07:11:12.000000Z", i.e. 3 hours behind).
 *
 * Works on the array form of a model, so nested eager-loaded relations (e.g. the
 * technician on a direct appointment) are formatted too. Only keys named created_at /
 * updated_at are touched; every other date-like field is left exactly as it was.
 *
 * Idempotent: an already formatted value comes back unchanged.
 */
final class DashboardDates
{
    public const FORMAT = 'Y-m-d H:i:s';

    private const KEYS = ['created_at', 'updated_at'];

    /** A DateTimeInterface or any parseable date string; null / '' / unparseable come back as they were. */
    public static function format(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            $date = \DateTimeImmutable::createFromInterface($value);
        } elseif (is_string($value) && $value !== '') {
            try {
                $date = new \DateTimeImmutable($value);
            } catch (\Throwable) {
                return $value;
            }
        } else {
            return $value;
        }

        // Laravel sets the PHP default timezone from config('app.timezone') at boot.
        return $date->setTimezone(new \DateTimeZone(date_default_timezone_get()))->format(self::FORMAT);
    }

    /** Recursively formats the created_at / updated_at keys of an array (nested arrays included). */
    public static function timestamps(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::timestamps($value);
            } elseif (in_array($key, self::KEYS, true)) {
                $data[$key] = self::format($value);
            }
        }

        return $data;
    }
}
