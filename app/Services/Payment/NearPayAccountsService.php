<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;

/**
 * Raw HTTP client for NearPay's Accounts API (terminal create/assign/unassign) — a separate API surface
 * from NearPayService (the Banks/SDK API, used for transaction lookups). Confirmed directly against the
 * caller's own working Postman requests and responses, not the public docs (not reachable from this
 * environment):
 *
 *   - Auth: the same literal `api-key` header as the Banks API.
 *   - Base URL: a SEPARATE config value (NEARPAY_ACCOUNTS_BASE_URL) — NEARPAY_BASE_URL already has
 *     /banks-sdk/ baked into it, so it cannot be reused verbatim for /terminals without producing the
 *     wrong path.
 *   - POST  {base_url}/terminals                  — Create Terminal
 *   - POST  {base_url}/terminals/{id}/assign       — Assign Terminal
 *   - PUT   {base_url}/terminals/{id}/unassign     — Unassign Terminal
 *
 * {id} in the assign/unassign paths is NearPay's own terminal UUID (the "terminal.id" field Create Terminal
 * returns) — not the trsm_code sent to create it, and not the tid Create Terminal also returns.
 */
class NearPayAccountsService
{
    private string $baseUrl;

    private string $apiKey;

    private string $merchantId;

    private int $timeout;

    private int $connectTimeout;

    public function __construct()
    {
        $this->baseUrl        = rtrim((string) config('services.nearpay.accounts_base_url', ''), '/');
        $this->apiKey         = rtrim((string) config('services.nearpay.api_key', ''));
        $this->merchantId     = (string) config('services.nearpay.merchant_id', '');
        $this->timeout        = (int) config('services.nearpay.timeout', 20);
        $this->connectTimeout = (int) config('services.nearpay.connect_timeout', 5);
    }

    private function assertConfigured(): void
    {
        if ($this->baseUrl === '' || $this->apiKey === '') {
            throw new \RuntimeException('NearPay Accounts API is not configured: NEARPAY_ACCOUNTS_BASE_URL and/or NEARPAY_API_KEY are empty.');
        }
    }

    private function client()
    {
        return Http::withHeaders(['api-key' => $this->apiKey])
            ->baseUrl($this->baseUrl)
            ->acceptJson()
            ->timeout($this->timeout)
            ->connectTimeout($this->connectTimeout);
    }

    /**
     * Returns the decoded "terminal" object: ['id' => <nearpay uuid>, 'tid' => ..., 'name' => ..., ...].
     *
     * @throws \RuntimeException  on a non-2xx response, a connection failure, or a malformed response
     */
    public function createTerminal(string $trsmCode, string $name): array
    {
        $this->assertConfigured();

        if ($this->merchantId === '') {
            throw new \RuntimeException('NearPay Accounts API is not configured: NEARPAY_MERCHANT_ID is empty.');
        }

        try {
            $response = $this->client()->post('terminals', [
                'trsm_code'   => $trsmCode,
                'merchant_id' => $this->merchantId,
                'name'        => $name,
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \RuntimeException('Could not reach NearPay: ' . $e->getMessage(), previous: $e);
        }

        if (! $response->successful()) {
            throw new \RuntimeException(
                'NearPay Create Terminal failed: ' . $this->describeError($response)
            );
        }

        $terminal = $response->json('terminal');

        if (! is_array($terminal) || empty($terminal['id']) || empty($terminal['tid'])) {
            throw new \RuntimeException('NearPay Create Terminal returned an unexpected response: ' . mb_substr($response->body(), 0, 500));
        }

        return $terminal;
    }

    /**
     * $nearpayTerminalId is NearPay's own terminal UUID (terminal.id from Create Terminal), not the local
     * row's id and not trsm_code/tid.
     *
     * @throws \RuntimeException  on a non-2xx response or a connection failure — the message is NearPay's
     *                            own human-readable error text (e.g. "terminal already assigned to another
     *                            user") when the response carries one, so callers can surface it directly.
     */
    public function assignTerminal(string $nearpayTerminalId, string $name, string $email, string $mobile): array
    {
        $this->assertConfigured();

        try {
            $response = $this->client()->post("terminals/{$nearpayTerminalId}/assign", [
                'name'   => $name,
                'email'  => $email,
                'mobile' => $this->toInternationalSaudiMobile($mobile),
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \RuntimeException('Could not reach NearPay: ' . $e->getMessage(), previous: $e);
        }

        if (! $response->successful()) {
            throw new \RuntimeException($this->describeError($response));
        }

        return (array) $response->json('terminal');
    }

    /**
     * NearPay rejects local-format numbers outright ("Please provide a valid mobile number") — confirmed
     * directly from a failed assign call with "0569347957" as sent. Local users store phone in local Saudi
     * format (e.g. "0569347957"), so this normalizes to the "+966..." shape NearPay actually requires,
     * regardless of which of the common input shapes a caller happens to have:
     *   "0569347957"    (local, leading 0)       -> "+966569347957"
     *   "569347957"     (local, no leading 0)    -> "+966569347957"
     *   "966569347957"  (international, no '+')  -> "+966569347957"
     *   "+966569347957" (already correct)        -> unchanged
     * Anything else (an unrecognized shape) is passed through as-is rather than guessed at further — NearPay
     * itself is still the final check, and a passthrough surfaces as the same clear validation error this
     * fix was written for, instead of being silently mangled into something equally wrong.
     */
    private function toInternationalSaudiMobile(string $mobile): string
    {
        $digits = preg_replace('/[^0-9]/', '', $mobile);

        if (str_starts_with($mobile, '+966')) {
            return '+966' . substr($digits, 3);
        }

        if (str_starts_with($digits, '966')) {
            return '+' . $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '+966' . substr($digits, 1);
        }

        if (strlen($digits) === 9) {
            return '+966' . $digits;
        }

        return $mobile;
    }

    /**
     * @throws \RuntimeException  on a non-2xx response or a connection failure
     */
    public function unassignTerminal(string $nearpayTerminalId): array
    {
        $this->assertConfigured();

        try {
            $response = $this->client()->put("terminals/{$nearpayTerminalId}/unassign");
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \RuntimeException('Could not reach NearPay: ' . $e->getMessage(), previous: $e);
        }

        if (! $response->successful()) {
            throw new \RuntimeException($this->describeError($response));
        }

        return (array) $response->json('terminal');
    }

    /**
     * Returns one page of NearPay's own GET /terminals listing: ['terminals' => [...], 'pages' => ['total'
     * => N, 'current' => N]] — the exact shape confirmed from the caller's own sample response.
     *
     * The pagination query parameter name is NOT confirmed against NearPay's own docs (unreachable from
     * this environment) — "page" is the standard REST convention and matches the response's own "current"
     * field, but this is an assumption, not something seen in a working request from the caller. If NearPay
     * ignores it and always returns page 1, that would show up as pages.total > 1 never advancing — worth
     * checking the very first time this runs against a merchant with more than one page of terminals.
     *
     * @throws \RuntimeException  on a non-2xx response, a connection failure, or a malformed response
     */
    public function listTerminals(int $page = 1): array
    {
        $this->assertConfigured();

        try {
            $response = $this->client()->get('terminals', ['page' => $page]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \RuntimeException('Could not reach NearPay: ' . $e->getMessage(), previous: $e);
        }

        if (! $response->successful()) {
            throw new \RuntimeException('NearPay List Terminals failed: ' . $this->describeError($response));
        }

        $terminals = $response->json('terminals');

        if (! is_array($terminals)) {
            throw new \RuntimeException('NearPay List Terminals returned an unexpected response: ' . mb_substr($response->body(), 0, 500));
        }

        return [
            'terminals' => $terminals,
            'pages'     => (array) $response->json('pages', ['total' => 1, 'current' => $page]),
        ];
    }

    /**
     * NearPay's error envelope (confirmed from the assign-failure sample) carries a human-readable message
     * at error.english — preferred over a raw status code when present, since it is what a caller should
     * actually show/log ("terminal already assigned to another user" rather than "HTTP 406").
     */
    private function describeError($response): string
    {
        $english = $response->json('error.english');

        if (is_string($english) && $english !== '') {
            return $english;
        }

        return 'HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 500);
    }
}
