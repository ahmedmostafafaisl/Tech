<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;

/**
 * Raw HTTP client for NearPay's Banks API — server-to-server only, never called from or exposed to the
 * mobile app. Confirmed from the official Banks API reference:
 *
 *   - Auth: a literal `api-key` header (not "Authorization: Bearer ..."), value from the NearPay dashboard.
 *   - Base URL: https://sandbox-api.nearpay.io/v1/banks-sdk/ (sandbox) — set via NEARPAY_BASE_URL.
 *   - GET /transactions/{id} ("Find Transaction", the SDK/phone integration endpoint — confirmed as the
 *     correct one for this app, not GET /pos/transactions/{uuid}, which is for dedicated POS hardware and
 *     has a different response shape entirely).
 *
 * Not confirmed by the documentation actually available (flagged, not guessed): the exact shape of a 404
 * response body, and whether /transactions (list) supports any filter/search query parameters — this
 * client exposes only the one confirmed, documented lookup.
 */
class NearPayService
{
    private string $baseUrl;

    private string $apiKey;

    private int $timeout;

    private int $connectTimeout;

    public function __construct()
    {
        $this->baseUrl        = rtrim((string) config('services.nearpay.base_url', ''), '/');
        $this->apiKey         = (string) config('services.nearpay.api_key', '');
        $this->timeout        = (int) config('services.nearpay.timeout', 20);
        $this->connectTimeout = (int) config('services.nearpay.connect_timeout', 5);
    }

    /**
     * GET /transactions/{id} — the server-side source of truth for one transaction. Returns the decoded
     * `transaction` object (not the whole response envelope) on success.
     *
     * @return array<string, mixed>|null  null only for a confirmed "transaction not found" (HTTP 404);
     *                                     every other failure throws, since it is NOT safe to treat as
     *                                     "unknown transaction" (a network blip must not look identical to
     *                                     "NearPay has never heard of this id").
     *
     * @throws \RuntimeException  on a non-404 HTTP error, a connection failure, or a malformed response
     */
    public function getTransaction(string $transactionId): ?array
    {
        if ($this->baseUrl === '' || $this->apiKey === '') {
            throw new \RuntimeException('NearPay is not configured: NEARPAY_BASE_URL and/or NEARPAY_API_KEY are empty.');
        }

        if (trim($transactionId) === '') {
            throw new \RuntimeException('NearPay transaction ID is empty.');
        }

        try {
            $response = Http::withHeaders(['api-key' => $this->apiKey])
                ->baseUrl($this->baseUrl)
                ->acceptJson()
                ->timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->get('transactions/' . rawurlencode($transactionId));
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \RuntimeException('Could not reach NearPay: ' . $e->getMessage(), previous: $e);
        }

        if ($response->status() === 404) {
            return null;
        }

        if (!$response->successful()) {
            throw new \RuntimeException(
                'NearPay Find Transaction failed. HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 500)
            );
        }

        $decoded = $response->json();

        if (!is_array($decoded) || !isset($decoded['transaction']) || !is_array($decoded['transaction'])) {
            throw new \RuntimeException('Malformed response from NearPay Find Transaction (no "transaction" object).');
        }

        return $decoded['transaction'];
    }
}
