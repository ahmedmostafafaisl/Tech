<?php

namespace App\Services\DY365;

/**
 * Should a failed read count toward a circuit breaker?
 *
 * The breakers exist to stop hammering DY when it is unhealthy. They used to count
 * EVERY null/exception, so five lookups of unknown book ids (DY answering 404/400
 * for each) opened the breaker for everyone: every appointment lookup then
 * returned "Dynamics unavailable" for up to a minute while DY was perfectly healthy.
 * Only failures that say something about DY itself — or our connection to it — count.
 */
final class DyFailureKind
{
    /** The request time budget ran out on OUR side; says nothing about DY's health. */
    private const BUDGET_MESSAGE = 'DY request budget exhausted';

    public static function countsAgainstBreaker(?int $status, bool $isConnectionError, ?string $message = null): bool
    {
        if ($message !== null && str_contains($message, self::BUDGET_MESSAGE)) {
            return false;
        }

        // timeouts, refused connections, DNS/TLS failures
        if ($isConnectionError) {
            return true;
        }

        if ($status !== null) {
            // rate limited / server errors / request timeout / auth problems (systemic: every
            // call will fail) count; any other 4xx is an answer about ONE request (404 unknown
            // record, 400/422 bad input) and does not.
            return $status === 429 || $status === 408 || $status === 401 || $status === 403 || $status >= 500;
        }

        // no status and not a connection error (e.g. "Missing access token"): unknown → counts,
        // exactly as before.
        return true;
    }
}
