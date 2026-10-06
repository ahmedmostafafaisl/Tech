<?php

namespace App\Services\Payment;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;

/**
 * Authenticates a Tamara notification.
 *
 * Tamara signs the notification with a JWT (HS256) keyed with the merchant's NOTIFICATION
 * TOKEN (merchant portal), not the API key. The token arrives as the `tamaraToken` query
 * parameter or as `Authorization: Bearer <jwt>`; the legacy `Tamara-Signature` header this
 * app used to read is accepted as a third place to look. A notification is genuine only if
 * at least one of those carries a JWT that verifies against the configured notification token.
 *
 * Fail-closed: no configured token, no token on the request, or a bad signature = rejected.
 */
final class TamaraWebhookSignature
{
    /** @return array<int, string> every JWT-looking value the request carries */
    public static function candidatesFrom(Request $request): array
    {
        $candidates = [
            $request->query('tamaraToken'),
            $request->input('tamaraToken'),
            $request->header('Tamara-Signature'),
        ];

        $authorization = (string) $request->header('Authorization', '');

        if (stripos($authorization, 'bearer ') === 0) {
            $candidates[] = substr($authorization, 7);
        }

        return array_values(array_unique(array_filter(
            array_map(fn ($value) => is_string($value) ? trim($value) : '', $candidates),
            fn (string $value) => $value !== ''
        )));
    }

    public static function isValid(string $jwt, ?string $notificationToken): bool
    {
        if ($jwt === '' || $notificationToken === null || $notificationToken === '') {
            return false;
        }

        $previousLeeway = JWT::$leeway;
        JWT::$leeway = 60;

        try {
            // The algorithm is pinned by the Key, so an "alg: none" or RS256 token cannot be substituted.
            JWT::decode($jwt, new Key($notificationToken, 'HS256'));

            return true;
        } catch (\Throwable $e) {
            return false;
        } finally {
            JWT::$leeway = $previousLeeway;
        }
    }

    /** @param array<int, string> $candidates */
    public static function authenticate(array $candidates, ?string $notificationToken): bool
    {
        foreach ($candidates as $candidate) {
            if (self::isValid($candidate, $notificationToken)) {
                return true;
            }
        }

        return false;
    }
}
