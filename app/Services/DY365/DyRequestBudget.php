<?php

namespace App\Services\DY365;

/**
 * Opt-in time budget for a web request that talks to DY.
 *
 * Cloudflare gives up at ~100s, and DyService's own timeouts are far longer
 * (60s x retries per read, 500s x 3 for change requests), so one slow DY call
 * is enough to 504 the whole request. An endpoint that must answer in a fixed
 * time calls begin() once; every DY call made during that request then shrinks
 * its timeout to what is left, and stops retrying when a second attempt would
 * not fit. With no budget active — every existing caller, every artisan
 * command — plan() returns the defaults untouched.
 *
 * Static on purpose: one request reaches DY through several DyService
 * instances (controller -> dispatcher -> another controller). Always pair
 * begin() with clear() in a finally block.
 */
final class DyRequestBudget
{
    private static ?float $deadline = null;

    public static function begin(float $seconds): void
    {
        self::$deadline = microtime(true) + $seconds;
    }

    public static function clear(): void
    {
        self::$deadline = null;
    }

    public static function active(): bool
    {
        return self::$deadline !== null;
    }

    /** Seconds left, or null when no budget is active. */
    public static function remaining(): ?float
    {
        return self::$deadline === null ? null : self::$deadline - microtime(true);
    }

    /** True once a budget is active and (almost) nothing is left of it. */
    public static function exhausted(): bool
    {
        $left = self::remaining();

        return $left !== null && $left < 1.0;
    }

    /**
     * [timeoutSeconds, attempts] for a call that normally uses
     * ($defaultTimeout, $defaultAttempts). $left is injectable for tests.
     *
     * @return array{0:int,1:int}
     */
    public static function plan(int $defaultTimeout, int $defaultAttempts, ?float $left = null): array
    {
        $left ??= self::remaining();

        if ($left === null) {
            return [$defaultTimeout, $defaultAttempts];
        }

        if ($left < 1.0) {
            throw new \RuntimeException('DY request budget exhausted');
        }

        $timeout = max(1, min($defaultTimeout, (int) floor($left)));

        // Retry only when a complete second attempt (plus the pause) still fits.
        $attempts = $left >= ($timeout * 2) + 2 ? max(1, $defaultAttempts) : 1;

        return [$timeout, $attempts];
    }
}
