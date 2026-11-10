<?php

namespace App\Services\Evaluation;

use Illuminate\Encryption\Encrypter;

/**
 * Maps an appointment's order_type to its evaluation/rating page — conceptually the same four-way split
 * EvaluationMessageController::createAndSendEvaluation() uses to pick a WhatsApp template name, kept here
 * as its own thing since that method needs a template name and this needs a URL; the categories themselves
 * must stay in sync between the two if either changes.
 *
 * Always resolves to a real link — a null, empty, or unrecognized order_type falls back to the sales
 * evaluation page rather than skipping the evaluation link entirely.
 */
class EvaluationLinkResolver
{
    private const DEFAULT_LINK = 'https://www.naqi.sa/rate-sales/';

    public static function resolve(?string $orderType): string
    {
        $orderType = trim((string) $orderType);

        return match (true) {
            in_array($orderType, ['تركيب'], true) => self::DEFAULT_LINK,
            in_array($orderType, ['صيانة دورية', 'خدمات'], true) => 'https://www.naqi.sa/rate-after-sales-service/',
            in_array($orderType, ['منتجات'], true) => 'https://www.naqi.sa/rate-products/',
            in_array($orderType, ['صيانة طارئة'], true) => 'https://www.naqi.sa/rate-urgent-maintenance/',
            default => self::DEFAULT_LINK,
        };
    }

    /**
     * The same base page resolve() returns, with OrderTypeId, book_id, tech_user_name and district appended
     * as a single encrypted query parameter — encrypted with this feature's OWN key (see encrypter()), not
     * the app's shared APP_KEY, so rotating or retiring one never affects the other, and nothing that reads
     * every other encrypted value in this app (sessions, other Crypt::encrypt() calls, queued job payloads,
     * etc. — all of which use APP_KEY) can also decode an evaluation link, or vice versa. The recipient
     * can't read or tamper with the data from the link alone; only this same app, with the same key, can
     * decode it back (see decodeData()).
     *
     * $data keys: OrderTypeId, book_id, tech_user_name, district — pass whatever refSingleAppointmentByBookId
     * already returned for this appointment; missing keys are simply encoded as null, nothing here requires
     * all four to be present.
     */
    public static function buildWithEncryptedData(?string $orderType, array $data): string
    {
        $payload = [
            'OrderTypeId'    => $data['OrderTypeId'] ?? null,
            'book_id'        => $data['book_id'] ?? null,
            'tech_user_name' => $data['tech_user_name'] ?? null,
            'district'       => $data['district'] ?? null,
        ];

        $encrypted = self::encrypter()->encrypt(json_encode($payload));

        return self::resolve($orderType) . '?data=' . urlencode($encrypted);
    }

    /**
     * The reverse of buildWithEncryptedData()'s query parameter — pass the raw, still-urlencoded value
     * exactly as it appears in the link (e.g. $request->query('data')), not something already urldecode()'d.
     * Throws Illuminate\Contracts\Encryption\DecryptException if the value is malformed or wasn't produced
     * by this same key — callers decide how to handle that (e.g. a 400 response), it isn't swallowed here,
     * since a failed decode is a very different situation from "there was no data at all".
     */
    public static function decodeData(string $encryptedQueryValue): array
    {
        $decrypted = self::encrypter()->decrypt(urldecode($encryptedQueryValue));

        return json_decode($decrypted, true) ?? [];
    }

    /**
     * A dedicated Encrypter instance (AES-256-CBC, same cipher APP_KEY itself uses), keyed by
     * EVALUATION_LINK_ENCRYPTION_KEY — never Crypt/APP_KEY. The env value follows the exact same
     * "base64:<...>" convention APP_KEY already uses, generated the same way: 32 random bytes, base64
     * encoded, prefixed with "base64:". Throws a clear RuntimeException (not a cryptic one from deep inside
     * Encrypter's own constructor) when the key is missing or the wrong length — this is deliberately a hard
     * failure, not a silent fallback to APP_KEY, since that would defeat the entire point of a separate key.
     */
    private static function encrypter(): Encrypter
    {
        $configured = (string) config('services.evaluation_link.key', '');

        if ($configured === '') {
            throw new \RuntimeException(
                'EVALUATION_LINK_ENCRYPTION_KEY is not set. Generate one with: '
                    . 'php -r "echo \'base64:\'.base64_encode(random_bytes(32)).PHP_EOL;" '
                    . 'and add it to .env, then clear config cache.'
            );
        }

        $key = str_starts_with($configured, 'base64:')
            ? base64_decode(substr($configured, 7))
            : $configured;

        if (strlen($key) !== 32) {
            throw new \RuntimeException(
                'EVALUATION_LINK_ENCRYPTION_KEY is not a valid 32-byte key for AES-256-CBC. '
                    . 'Generate a new one with: php -r "echo \'base64:\'.base64_encode(random_bytes(32)).PHP_EOL;"'
            );
        }

        return new Encrypter($key, 'AES-256-CBC');
    }
}
