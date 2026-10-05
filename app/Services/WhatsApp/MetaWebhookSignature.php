<?php

namespace App\Services\WhatsApp;

/**
 * Verifies Meta's X-Hub-Signature-256 header on WhatsApp webhook POSTs.
 *
 * Meta signs the exact raw request body with the app secret
 * ("sha256=" + hex HMAC-SHA256). Without this check /WhatsApp/receive accepts
 * a POST from anyone, and an inbound "cancel"/"reschedule" button reply is
 * turned into a change request in Dynamics.
 */
final class MetaWebhookSignature
{
    public static function isValid(string $rawBody, ?string $header, string $appSecret): bool
    {
        if ($appSecret === '' || $header === null || ! str_starts_with($header, 'sha256=')) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $appSecret);

        return hash_equals($expected, $header);
    }
}
