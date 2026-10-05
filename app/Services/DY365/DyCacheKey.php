<?php

namespace App\Services\DY365;

/**
 * Cache keys for everything DyService caches (token, data, circuit breakers).
 *
 * The DY base URL is switchable at runtime (dy_environments / `dy:environment`),
 * and the token is requested FOR that URL (resource = baseUrl). With one fixed
 * key per kind of data, switching environment kept serving the old
 * environment's token for up to an hour and its cached data (including the
 * 10-minute stale stock) until it expired. Putting a short id of the
 * environment in every key makes each environment's cache independent — a
 * switch can neither read another environment's entries nor trip its breaker.
 */
final class DyCacheKey
{
    /** Short, stable id of an environment; trailing slash and case don't matter. */
    public static function env(string $baseUrl): string
    {
        return substr(md5(strtolower(rtrim(trim($baseUrl), '/'))), 0, 8);
    }

    public static function make(string $baseUrl, string $suffix): string
    {
        return 'dy:' . self::env($baseUrl) . ':' . ltrim($suffix, ':');
    }
}
