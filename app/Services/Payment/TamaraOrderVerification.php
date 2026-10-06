<?php

namespace App\Services\Payment;

/**
 * Decides what a Tamara order (as returned by GET /orders/{id}) means for ONE local DY payment link.
 * Pure logic: no HTTP, no database.
 *
 * The order id in a redirect URL comes from the browser, so it proves nothing by itself. An order counts
 * only if Tamara reports the DY reference we sent when the order was created (order_reference_id), for the
 * same amount, in SAR. A mismatch must change nothing.
 *
 * TamaraService::getOrderStatus() swallows exceptions and returns ['error' => true, ...]; that shape is an
 * ERROR here, never an approval.
 */
final class TamaraOrderVerification
{
    public const NEEDS_AUTHORISE = 'needs_authorise';   // approved by Tamara, the merchant still has to authorise

    public const AUTHORISED = 'authorised';             // authorised: capture is the next step

    public const CAPTURED = 'captured';                 // captured / fully captured

    public const PENDING = 'pending';                   // new, or any status we do not know: change nothing

    public const REJECTED = 'rejected';                 // declined / expired / cancelled

    public const MISMATCH = 'mismatch';                 // another order, wrong amount or currency

    public const ERROR = 'error';                       // no usable reply from Tamara

    /**
     * @param  array<string, mixed>  $order  decoded Tamara order, or the ['error' => true] shape
     * @return array{verdict: string, reason: string}
     */
    public static function evaluate(array $order, string $dyReference, float|string|int $amount, string $currency = 'SAR'): array
    {
        if ($order === [] || ! empty($order['error']) || ! isset($order['status'])) {
            return self::result(self::ERROR, 'no usable reply from Tamara');
        }

        $reference = (string) ($order['order_reference_id'] ?? '');

        if ($dyReference === '' || $reference === '' || ! hash_equals($dyReference, $reference)) {
            return self::result(self::MISMATCH, 'order reference does not match this payment link');
        }

        $total = $order['total_amount']['amount'] ?? null;

        if (! is_numeric($total) || abs((float) $total - (float) $amount) >= 0.005) {
            return self::result(self::MISMATCH, 'amount does not match this payment link');
        }

        if (strtoupper((string) ($order['total_amount']['currency'] ?? '')) !== strtoupper($currency)) {
            return self::result(self::MISMATCH, 'currency does not match this payment link');
        }

        return match (strtolower((string) $order['status'])) {
            'approved'                              => self::result(self::NEEDS_AUTHORISE, 'order approved, awaiting authorisation'),
            'authorised', 'authorized'              => self::result(self::AUTHORISED, 'order authorised'),
            'captured', 'fully_captured'            => self::result(self::CAPTURED, 'order captured'),
            'declined', 'expired', 'canceled', 'cancelled' => self::result(self::REJECTED, 'order declined, expired or cancelled'),
            default                                 => self::result(self::PENDING, 'order not approved yet'),
        };
    }

    private static function result(string $verdict, string $reason): array
    {
        return ['verdict' => $verdict, 'reason' => $reason];
    }
}
