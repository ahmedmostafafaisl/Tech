<?php

namespace App\Services\DY365;

/**
 * Whether a failed DY call may be sent again.
 *
 * Every DY endpoint is an HTTP POST — reads included — so the verb says nothing
 * about whether repeating a call is harmless. DyService marks each operation
 * instead:
 *
 *  - safe (reads, set-a-value updates): retry on any connection error and on
 *    429/500/502/503/504. This is what DyService has always done.
 *  - NOT safe (create / cancel / complete / add-attachments …): a read timeout
 *    or a 5xx can mean DY already processed the request and only the answer
 *    was lost, so repeating it duplicates the effect or reports a misleading
 *    "already done" error. These retry only when DY certainly did not process
 *    the request: it was never sent (DNS/connect/TLS failure), or DY refused it
 *    up front (429 rate limit, 503 unavailable).
 */
final class DyRetryPolicy
{
    private const SAFE_STATUSES   = [429, 500, 502, 503, 504];
    private const UNSAFE_STATUSES = [429, 503];

    public static function shouldRetry(bool $safeToRetry, bool $isConnectionError, ?string $message, ?int $status): bool
    {
        if ($safeToRetry) {
            return $isConnectionError || in_array($status, self::SAFE_STATUSES, true);
        }

        if ($isConnectionError) {
            return DyTransportOutcome::fromTransportError((string) $message)['outcome'] === DyTransportOutcome::NOT_SENT;
        }

        return in_array($status, self::UNSAFE_STATUSES, true);
    }
}
