<?php

namespace App\Services\Payment;

/**
 * Decides what a Tabby payment object (as returned by GET /payments/{id}) means for
 * ONE local payment. Pure logic: no HTTP, no database.
 *
 * "Paid" is only ever the verdict when the provider says the payment is CLOSED *and* it
 * demonstrably belongs to this local payment: same order reference, same amount, same
 * currency. Anything that does not match is a MISMATCH and must change nothing — not even
 * to "failed" — because the id came from the browser and cannot be trusted.
 */
final class TabbyPaymentVerification
{
    public const PAID = 'paid';                    // CLOSED and everything matches

    public const NEEDS_CAPTURE = 'needs_capture';  // AUTHORIZED and everything matches

    public const PENDING = 'pending';              // not completed yet (e.g. CREATED): change nothing

    public const REJECTED = 'rejected';            // REJECTED / EXPIRED: it will never be paid

    public const MISMATCH = 'mismatch';            // belongs to another order, or amount/currency differ

    /**
     * @param  array<string, mixed>  $provider  decoded Tabby payment object
     * @return array{verdict: string, reason: string}
     */
    public static function evaluate(array $provider, string $referenceId, float|string|int $amount, string $currency = 'SAR'): array
    {
        $providerReference = (string) ($provider['order']['reference_id'] ?? '');

        if ($referenceId === '' || $providerReference === '' || ! hash_equals($referenceId, $providerReference)) {
            return self::result(self::MISMATCH, 'order reference does not match this payment');
        }

        $providerAmount = $provider['amount'] ?? null;

        if (! is_numeric($providerAmount) || abs((float) $providerAmount - (float) $amount) >= 0.005) {
            return self::result(self::MISMATCH, 'amount does not match this payment');
        }

        if (strtoupper((string) ($provider['currency'] ?? '')) !== strtoupper($currency)) {
            return self::result(self::MISMATCH, 'currency does not match this payment');
        }

        return match (strtoupper((string) ($provider['status'] ?? ''))) {
            'CLOSED'              => self::result(self::PAID, 'payment is closed at Tabby'),
            'AUTHORIZED'          => self::result(self::NEEDS_CAPTURE, 'payment is authorized and must be captured'),
            'REJECTED', 'EXPIRED' => self::result(self::REJECTED, 'payment was rejected or expired at Tabby'),
            default               => self::result(self::PENDING, 'payment is not completed at Tabby'),
        };
    }

    private static function result(string $verdict, string $reason): array
    {
        return ['verdict' => $verdict, 'reason' => $reason];
    }
}
