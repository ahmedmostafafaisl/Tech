<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Cache;

/**
 * Failed-attempt counter for ONE account and ONE secret (the OTP or the PIN).
 *
 * This is deliberately not general rate limiting: it only exists so a 4-digit
 * code cannot be guessed. It is keyed by user id, not by IP (the client IP is
 * currently the load balancer's), and it is never reset by "send another OTP",
 * otherwise an attacker could alternate resend + guess forever.
 *
 * It lives in the cache. With the default `file` driver that is per server; use
 * a shared driver (redis / database) for a limit that holds across instances.
 */
final class AuthAttemptLimiter
{
    public const MAX_ATTEMPTS = 5;

    public const DECAY_SECONDS = 600;

    private function __construct(
        private readonly string $key,
        private readonly int $maxAttempts,
        private readonly int $decaySeconds,
    ) {
    }

    public static function otp(int|string $userId): self
    {
        return new self("auth:otp-attempts:{$userId}", self::MAX_ATTEMPTS, self::DECAY_SECONDS);
    }

    public static function pin(int|string $userId): self
    {
        return new self("auth:pin-attempts:{$userId}", self::MAX_ATTEMPTS, self::DECAY_SECONDS);
    }

    public function tooManyAttempts(): bool
    {
        return (int) Cache::get($this->key, 0) >= $this->maxAttempts;
    }

    /** Records one failed attempt and returns how many are on record now. */
    public function hit(): int
    {
        Cache::add($this->key, 0, $this->decaySeconds);

        return (int) Cache::increment($this->key);
    }

    public function clear(): void
    {
        Cache::forget($this->key);
    }
}
