<?php

namespace App\Services\Payment;

use App\Models\DyPaymentLink;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AlinmaPay Payment Gateway — Hosted Payment Page integration (AlinmaPay API Specification v3.7).
 *
 * Mirrors ClickPayService's "dy" pattern (dyCreateInvoice/queryPayment) as closely as the two gateways'
 * actual protocols allow: a DyPaymentLink row (payment_method='alinmapay') is the one local record of each
 * attempt, created here with the gateway's own paymentId/transactionId, then completed — same place, same
 * code — via DyPaymentLinkTransitions::complete(), exactly like ClickPay/Tabby/Tamara already do.
 *
 * Unlike ClickPay (a separate browser "return" redirect and a separate server "callback" webhook),
 * AlinmaPay has exactly one mechanism: the receiptUrl passed in additionalDetails.userData, which receives
 * the final, AES-encrypted response once (Section 7) — handled by AlinmaPayController::receipt().
 */
class AlinmaPayService
{
    protected string $baseUrl;
    protected string $terminalId;
    protected string $password;
    protected string $merchantKey;
    protected string $publicUrl;

    public function __construct()
    {
        $this->baseUrl     = rtrim((string) config('services.alinmapay.base_url', ''), '/');
        $this->terminalId  = (string) config('services.alinmapay.terminal_id', '');
        $this->password    = (string) config('services.alinmapay.password', '');
        $this->merchantKey = (string) config('services.alinmapay.merchant_key', '');
        $this->publicUrl   = rtrim((string) config('services.alinmapay.public_url', config('app.url')), '/');
    }

    /**
     * Builds an absolute callback/receipt URL using $this->publicUrl as the domain instead of route()'s
     * default host — same approach as ClickPayService::publicRoute().
     */
    protected function publicRoute(string $name, array $params = []): string
    {
        $path = route($name, $params, false);

        return $this->publicUrl . $path;
    }

    /**
     * Request signature (Section 6) — sha256(trackId|terminalId|password|merchantKey|amount|currency).
     * trackId is the merchant's own order.orderId (the spec's sample request uses a "_Track"-suffixed
     * value for it, but every actual request field in the spec is keyed by order.orderId, not a separate
     * "trackId" field — so this takes the same value that goes into the request's order.orderId).
     */
    public function signRequest(string $trackId, string $amount, string $currency): string
    {
        $pipeSeparated = implode('|', [$trackId, $this->terminalId, $this->password, $this->merchantKey, $amount, $currency]);

        return hash('sha256', $pipeSeparated);
    }

    /**
     * Response signature (Section 6) — sha256(paymentId|merchantKey|responseCode|amount). Used to validate
     * a response that itself carries a plaintext "signature" field (e.g. the Void/Inquiry/B2B samples) —
     * not the encrypted receipt response, which AlinmaPay doesn't sign this same way (see decryptResponse()).
     */
    public function signResponse(string $paymentId, string $responseCode, string $amount): string
    {
        $pipeSeparated = implode('|', [$paymentId, $this->merchantKey, $responseCode, $amount]);

        return hash('sha256', $pipeSeparated);
    }

    /**
     * Decrypts the receiptUrl's "data" parameter (Section 7): base64-decode, then AES-ECB-decrypt with the
     * merchant key's raw bytes (the key is given as a hex string) as the AES key — exactly what the spec's
     * Java sample does (Cipher.getInstance("AES") with no mode/padding specified defaults, in Java's SunJCE
     * provider, to AES/ECB/PKCS5Padding; PHP's OPENSSL_RAW_DATA + openssl_decrypt's automatic PKCS7-padding
     * removal is the equivalent here — PKCS5 and PKCS7 padding are identical for AES's 16-byte block size).
     * The AES variant (128/192/256) is picked from the actual decoded key length, not assumed, since a hex
     * merchant key can be any of the three.
     *
     * @throws \RuntimeException if the merchant key is misconfigured, the payload isn't valid base64, or
     *   decryption/padding fails (a wrong key produces garbage padding, which openssl_decrypt reports as
     *   failure rather than silently returning corrupted plaintext).
     */
    public function decryptResponse(string $encryptedData): string
    {
        $keyBytes = $this->hexToBytes($this->merchantKey);

        $cipher = match (strlen($keyBytes)) {
            16 => 'AES-128-ECB',
            24 => 'AES-192-ECB',
            32 => 'AES-256-ECB',
            default => throw new \RuntimeException(
                'ALINMAPAY_MERCHANT_KEY must decode to 16, 24, or 32 bytes (got ' . strlen($keyBytes) . '); check it is the exact hex string AlinmaPay issued.'
            ),
        };

        $decoded = base64_decode($encryptedData, true);

        if ($decoded === false) {
            throw new \RuntimeException('AlinmaPay receipt "data" parameter is not valid base64.');
        }

        $plaintext = openssl_decrypt($decoded, $cipher, $keyBytes, OPENSSL_RAW_DATA);

        if ($plaintext === false) {
            throw new \RuntimeException('Failed to decrypt AlinmaPay receipt response — wrong merchant key, or the payload was tampered with.');
        }

        return $plaintext;
    }

    /**
     * Hex string ("A1B2...") to its raw bytes — PHP's own equivalent of the spec's Java
     * hexStringToByteArray(), used both here and wherever the merchant key is needed as raw AES key bytes.
     */
    private function hexToBytes(string $hex): string
    {
        if ($hex === '' || ! ctype_xdigit($hex) || strlen($hex) % 2 !== 0) {
            throw new \RuntimeException('ALINMAPAY_MERCHANT_KEY is not configured as a valid hex string.');
        }

        return hex2bin($hex);
    }

    /**
     * Leg 1 (Section 4.1): POSTs to {{domain}}/{{pg.context.path}}/v2/payments/pay-request, creating a
     * DyPaymentLink row (payment_method='alinmapay') the same moment AlinmaPay hands back a paymentId —
     * mirroring ClickPayService::dyCreateInvoice()'s own "create the local row right after the gateway
     * accepts the request" sequencing. Returns the hosted payment page URL (Leg 2's target) for the caller
     * to redirect the customer to.
     *
     * $data: book_id, phone, name, email, street1, city, state, zip (all optional — see the per-field
     * fallbacks below, matching ClickPayService::dyCreateInvoice()'s own style).
     *
     * @return array{success: bool, redirect_url?: string, payment_id?: string, transaction_id?: string, message?: string, response?: mixed}
     */
    public function createPayment(array $data, float $amount, string $orderId, array $items = []): array
    {
        $amountStr = number_format($amount, 2, '.', '');
        $currency  = 'SAR';
        $signature = $this->signRequest($orderId, $amountStr, $currency);

        $receiptUrl = $this->publicRoute('alinmapay.receipt');

        $payload = [
            'terminalId' => $this->terminalId,
            'password'   => $this->password,
            'signature'  => $signature,
            'paymentType' => '1', // purchase — see paymentType in createPreAuth() for pre-authorization
            'amount'     => $amountStr,
            'currency'   => $currency,
            'order'      => [
                'orderId'     => $orderId,
                'description' => $data['description'] ?? "Appointment booking {$orderId}",
            ],
            'customer' => [
                'customerEmail'            => $data['email'] ?? 'customer@naqi.sa',
                'billingAddressStreet'     => $data['street1'] ?? 'N/A',
                'billingAddressCity'       => $data['city'] ?? 'Riyadh',
                'billingAddressState'      => $data['state'] ?? 'Riyadh',
                'billingAddressPostalCode' => $data['zip'] ?? '00000',
                'billingAddressCountry'    => 'SA',
            ],
            'additionalDetails' => [
                'userData' => json_encode(['receiptUrl' => $receiptUrl], JSON_UNESCAPED_SLASHES),
            ],
        ];

        try {
            $response = Http::post("{$this->baseUrl}/v2/payments/pay-request", $payload);
            $json = $response->json();

            $paymentId = $this->extractPaymentId($json);

            if ($response->successful() && ! empty($json['transactionId']) && $paymentId !== null) {
                $link = DyPaymentLink::create([
                    'payment_method'   => 'alinmapay',
                    'payment_reference_id' => $json['transactionId'],
                    'payment_id'       => $paymentId,
                    'dy_reference_id'  => $orderId,
                    'checkout_url'     => rtrim($this->baseUrl, '/') . '/direct.htm?paymentId=' . $paymentId,
                    'status'           => 'created',
                    'amount'           => $amount,
                    'phone'            => $data['phone'] ?? null,
                    'customer_account' => $data['custAccount'] ?? null,
                    'book_id'          => $data['book_id'] ?? null,
                ]);

                if (! empty($items)) {
                    $link->lines()->createMany($items);
                }

                return [
                    'success'         => true,
                    'redirect_url'    => $link->checkout_url,
                    'payment_id'      => $paymentId,
                    'transaction_id'  => $json['transactionId'],
                    'response'        => $json,
                ];
            }

            Log::error('AlinmaPay createPayment: gateway did not accept the request', ['response' => $json]);

            return [
                'success'  => false,
                'message'  => $json['responseDescription'] ?? 'Payment initiation failed',
                'response' => $json,
            ];
        } catch (\Throwable $e) {
            Log::error('AlinmaPay createPayment error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * AlinmaPay's own pay-request response doesn't carry a distinct "paymentId" field by that name (Section
     * 4.1's sample response has only paymentLink.linkUrl, which is the un-filled template
     * ".../direct.htm?paymentId=" — the actual id is appended server-side when the merchant is redirected,
     * per Leg 2's instructions). transactionId (the gateway's own unique id for this attempt) is used as
     * the paymentId in that case, since it is the only identifier AlinmaPay's create response actually
     * provides; if a future AlinmaPay response ever includes a distinct paymentId value, that takes
     * precedence here.
     */
    private function extractPaymentId(?array $json): ?string
    {
        if ($json === null) {
            return null;
        }

        if (! empty($json['paymentId'])) {
            return (string) $json['paymentId'];
        }

        $linkUrl = $json['paymentLink']['linkUrl'] ?? null;

        if (is_string($linkUrl) && preg_match('/paymentId=([^&]+)/', $linkUrl, $m) && $m[1] !== '') {
            return $m[1];
        }

        return ! empty($json['transactionId']) ? (string) $json['transactionId'] : null;
    }

    /**
     * Server-side verification independent of the receipt callback (Section 5.2, paymentType "10") —
     * queries AlinmaPay directly for a transaction's real, current status. Used the same way
     * ClickPayService::queryPayment() is: a plain query method, with all status-interpreting/DB-updating
     * logic left to the caller.
     */
    public function inquiry(string $referenceId, string $orderId, float $amount): array
    {
        $amountStr = number_format($amount, 2, '.', '');
        $currency  = 'SAR';

        $payload = [
            'referenceId' => $referenceId,
            'terminalId'  => $this->terminalId,
            'password'    => $this->password,
            'signature'   => $this->signRequest($orderId, $amountStr, $currency),
            'paymentType' => '10',
            'amount'      => $amountStr,
            'currency'    => $currency,
            'order'       => ['orderId' => $orderId],
        ];

        $response = Http::post("{$this->baseUrl}/v2/payments/pay-request", $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('AlinmaPay inquiry failed. HTTP ' . $response->status() . ': ' . $response->body());
        }

        return $response->json();
    }
}
