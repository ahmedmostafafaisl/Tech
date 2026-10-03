<?php

namespace App\Services\DY365;

/**
 * What we know after trying to POST something to DY — in particular whether
 * DY may have received it. The old helper collapsed every failure to null,
 * so "DY never got it" and "DY got it but never answered" looked the same,
 * and callers treated null as success.
 */
final class DyTransportOutcome
{
    /** DY replied; 'status' and 'body' are meaningful. */
    public const ANSWERED = 'answered';

    /** DY may have received it but we got no usable answer (timeout, 5xx, reset). */
    public const UNCONFIRMED = 'unconfirmed';

    /** DY definitely never received it (could not resolve/connect, no token). */
    public const NOT_SENT = 'not_sent';

    public static function answered(int $status, ?array $body): array
    {
        return ['outcome' => self::ANSWERED, 'status' => $status, 'body' => $body, 'error' => null];
    }

    public static function unconfirmed(string $error, ?int $status = null): array
    {
        return ['outcome' => self::UNCONFIRMED, 'status' => $status, 'body' => null, 'error' => $error];
    }

    public static function notSent(string $error): array
    {
        return ['outcome' => self::NOT_SENT, 'status' => null, 'body' => null, 'error' => $error];
    }

    /**
     * 2xx/3xx/4xx are real answers (4xx = DY refused it). 5xx is a gateway or
     * application error whose effect is unknown, so it is "unconfirmed".
     */
    public static function fromResponse(int $status, mixed $json): array
    {
        if ($status >= 500) {
            return self::unconfirmed("Dynamics replied with HTTP {$status}", $status);
        }

        return self::answered($status, is_array($json) ? $json : null);
    }

    /**
     * Classify a transport exception message. Connect/DNS/TLS failures mean the
     * request never left; a read timeout or reset after sending means it may
     * have been processed.
     */
    public static function fromTransportError(string $message): array
    {
        $neverSent = (bool) preg_match(
            '/Could not resolve host|Resolving timed out|Failed to connect|Connection refused|'
                . 'Connection timed out after|SSL connect error|cURL error (6|7|35|60)\b/i',
            $message
        );

        return $neverSent ? self::notSent($message) : self::unconfirmed($message);
    }
}
