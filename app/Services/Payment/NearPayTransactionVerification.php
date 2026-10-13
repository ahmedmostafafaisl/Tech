<?php

namespace App\Services\Payment;

/**
 * Decides what a NearPay transaction (as returned by GET /banks-sdk/transactions/{id}) means for ONE local
 * payment. Pure logic: no HTTP, no database. Mirrors TabbyPaymentVerification's shape.
 *
 * Confirmed from the NearPay documentation:
 *   - A transaction has a `receipts` array (NOT a single flat object) — the docs do not explain why there
 *     can be more than one, so this scans ALL of them rather than assuming receipts[0] is authoritative:
 *     it looks for the most conclusive outcome (an approved, non-refunded, non-reversed purchase first;
 *     then a refund; then a reversal; only REJECTED if every receipt was declined).
 *   - A refund is NOT a separate transaction state — the docs are explicit that it is the SAME "approved"
 *     shape with is_refunded: true on that receipt, so REFUNDED is checked before treating is_approved as
 *     a plain paid purchase.
 *   - amount_authorized.value and currency.english are both STRINGS. Compared with bcmath (bccomp), never
 *     as floats, per the exact-decimal requirement — a float comparison of monetary strings is exactly the
 *     class of bug that lets a fraction-of-a-cent rounding difference silently pass as "paid".
 */
final class NearPayTransactionVerification
{
    public const PAID = 'paid';            // an approved, non-refunded, non-reversed purchase receipt, amount/currency match

    public const REFUNDED = 'refunded';    // an approved receipt with is_refunded: true

    public const REVERSED = 'reversed';    // an approved receipt with is_reversed: true (and not refunded)

    public const REJECTED = 'rejected';    // every receipt is_approved: false — it will never be paid

    public const MISMATCH = 'mismatch';    // a conclusive (paid/refunded/reversed) receipt exists, but the amount or currency does not match

    public const INCONCLUSIVE = 'inconclusive';  // no receipts at all, or the response could not be read — change nothing

    /**
     * @param  array<string, mixed>  $transaction  decoded NearPay transaction object (the "transaction" key
     *                                              of GET /banks-sdk/transactions/{id}'s response)
     * @return array{verdict: string, reason: string, receipt: array<string, mixed>|null}
     */
    public static function evaluate(array $transaction, string $expectedAmount, string $expectedCurrency = 'SAR'): array
    {
        $receipts = $transaction['receipts'] ?? null;

        if (!is_array($receipts) || $receipts === []) {
            return self::result(self::INCONCLUSIVE, 'the transaction has no receipts to evaluate', null);
        }

        $paid = null;
        $refunded = null;
        $reversed = null;
        $sawAnyApproved = false;

        foreach ($receipts as $receipt) {
            if (!is_array($receipt)) {
                continue;
            }

            if (!($receipt['is_approved'] ?? false)) {
                continue;
            }

            $sawAnyApproved = true;

            if ($receipt['is_refunded'] ?? false) {
                $refunded ??= $receipt;
            } elseif ($receipt['is_reversed'] ?? false) {
                $reversed ??= $receipt;
            } else {
                $paid ??= $receipt;
            }
        }

        // Precedence: an approved, untouched purchase receipt wins if one exists at all (the normal case).
        // Otherwise prefer reporting a refund or reversal over a bare rejection, since those are far more
        // consequential outcomes to miss.
        $receipt = $paid ?? $refunded ?? $reversed;

        if ($receipt === null) {
            return $sawAnyApproved
                ? self::result(self::INCONCLUSIVE, 'an approved receipt exists but is neither a plain purchase, a refund, nor a reversal', null)
                : self::result(self::REJECTED, 'every receipt on this transaction was declined', null);
        }

        $amountCheck = self::amountMatches($receipt, $expectedAmount);

        if ($amountCheck !== true) {
            return self::result(self::MISMATCH, $amountCheck, $receipt);
        }

        $currencyCheck = self::currencyMatches($receipt, $expectedCurrency);

        if ($currencyCheck !== true) {
            return self::result(self::MISMATCH, $currencyCheck, $receipt);
        }

        return match (true) {
            $receipt === $refunded => self::result(self::REFUNDED, 'the matching receipt is a confirmed refund', $receipt),
            $receipt === $reversed => self::result(self::REVERSED, 'the matching receipt is a confirmed reversal', $receipt),
            default                => self::result(self::PAID, 'the matching receipt is an approved, unmodified purchase', $receipt),
        };
    }

    /** @return true|string  true if it matches, otherwise the mismatch reason */
    private static function amountMatches(array $receipt, string $expectedAmount): bool|string
    {
        $actual = $receipt['amount_authorized']['value'] ?? null;

        if (!is_string($actual) && !is_numeric($actual)) {
            return 'the receipt has no readable amount_authorized.value';
        }

        if (!self::isDecimalString((string) $actual) || !self::isDecimalString($expectedAmount)) {
            return 'the amount could not be compared as an exact decimal value';
        }

        return bccomp((string) $actual, $expectedAmount, 2) === 0
            ? true
            : "amount does not match this payment (NearPay: {$actual}, expected: {$expectedAmount})";
    }

    /** @return true|string */
    private static function currencyMatches(array $receipt, string $expectedCurrency): bool|string
    {
        $actual = $receipt['currency']['english'] ?? null;

        if (!is_string($actual) || $actual === '') {
            return 'the receipt has no readable currency.english';
        }

        return strcasecmp($actual, $expectedCurrency) === 0
            ? true
            : "currency does not match this payment (NearPay: {$actual}, expected: {$expectedCurrency})";
    }

    private static function isDecimalString(string $value): bool
    {
        return (bool) preg_match('/^-?\d+(\.\d+)?$/', trim($value));
    }

    /** @param array<string, mixed>|null $receipt */
    private static function result(string $verdict, string $reason, ?array $receipt): array
    {
        return ['verdict' => $verdict, 'reason' => $reason, 'receipt' => $receipt];
    }
}
