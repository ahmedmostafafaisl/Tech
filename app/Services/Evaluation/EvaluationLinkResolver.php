<?php

namespace App\Services\Evaluation;

use Illuminate\Support\Facades\Crypt;

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
     * as a single encrypted query parameter — encrypted with Laravel's Crypt (AES-256-CBC, keyed by this
     * app's own APP_KEY), the key "known only to" this app, never shared with or derivable by the recipient.
     * The recipient can't read or tamper with the data from the link alone; only this same app, with the
     * same key, can decode it back (see decodeData()) — there is no separate decryption step exposed to
     * anyone outside it.
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

        $encrypted = Crypt::encryptString(json_encode($payload));

        return self::resolve($orderType) . '?data=' . urlencode($encrypted);
    }

    /**
     * The reverse of buildWithEncryptedData()'s query parameter — pass the raw, still-urlencoded value
     * exactly as it appears in the link (e.g. $request->query('data')), not something already urldecode()'d.
     * Throws Illuminate\Contracts\Encryption\DecryptException if the value is malformed or wasn't produced
     * by this same app's key — callers decide how to handle that (e.g. a 400 response), it isn't swallowed
     * here, since a failed decode is a very different situation from "there was no data at all".
     */
    public static function decodeData(string $encryptedQueryValue): array
    {
        $decrypted = Crypt::decryptString(urldecode($encryptedQueryValue));

        return json_decode($decrypted, true) ?? [];
    }
}
