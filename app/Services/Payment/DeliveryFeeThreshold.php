<?php

namespace App\Services\Payment;

/**
 * The delivery-fee rule for تركيب / منتجات appointments, in one place.
 *
 *   goods subtotal < 500  → the appointment needs a delivery fee
 *                           (fes-transportation, or naqi-s00004, or fes-tech-visit)
 *   goods subtotal >= 500 → it must NOT carry fes-transportation
 *
 * "Goods subtotal" is TotalAmountSum WITHOUT the lines that are the fee itself.
 * The rule used to compare the raw TotalAmountSum, which already contains the
 * delivery fee, so it contradicted itself: goods of 499 + a 35 fee = 534 ≥ 500
 * → "unexpected delivery fee", while the same appointment without the fee
 * (499 < 500) → "missing delivery fee". Any appointment with goods between 465
 * and 499 had no valid configuration at all.
 *
 * Previously this logic was copy-pasted into sendPaymentLinks(),
 * completeAppointment() and PaymentCompletionDispatcher; all three now call here.
 */
final class DeliveryFeeThreshold
{
    public const THRESHOLD = 500.0;

    /** fes-tech-visit never counts toward the threshold (existing behaviour, kept as-is). */
    public const TECH_VISIT_PRICE = 35.0;

    public const MISSING    = 'missing_delivery_fee';
    public const UNEXPECTED = 'unexpected_delivery_fee';

    /**
     * TotalAmountSum minus the delivery-fee line(s) (their real amounts, not an
     * assumed 35) and minus the fes-tech-visit price.
     */
    public static function subtotal(float $totalAmountSum, iterable $salesLines): float
    {
        $f = self::flags($salesLines);

        return round($totalAmountSum - $f['feeAmount'] - ($f['techVisit'] ? self::TECH_VISIT_PRICE : 0.0), 2);
    }

    /** null when the appointment satisfies the rule, otherwise MISSING / UNEXPECTED. */
    public static function violation(float $totalAmountSum, iterable $salesLines): ?string
    {
        $f = self::flags($salesLines);

        $subtotal = round($totalAmountSum - $f['feeAmount'] - ($f['techVisit'] ? self::TECH_VISIT_PRICE : 0.0), 2);

        if ($subtotal < self::THRESHOLD) {
            return (! $f['techVisit'] && ! $f['fee'] && ! $f['naqi']) ? self::MISSING : null;
        }

        return $f['fee'] ? self::UNEXPECTED : null;
    }

    /** @return array{fee:bool,feeAmount:float,techVisit:bool,naqi:bool} */
    private static function flags(iterable $salesLines): array
    {
        $out = ['fee' => false, 'feeAmount' => 0.0, 'techVisit' => false, 'naqi' => false];

        foreach ($salesLines as $line) {
            $item = strtolower(trim((string) ($line['ItemNumber'] ?? '')));

            if ($item === 'fes-transportation') {
                $out['fee']        = true;
                $out['feeAmount'] += self::lineAmount($line);
            } elseif ($item === 'fes-tech-visit') {
                $out['techVisit'] = true;
            } elseif ($item === 'naqi-s00004') {
                $out['naqi'] = true;
            }
        }

        return $out;
    }

    private static function lineAmount(array|\ArrayAccess $line): float
    {
        if (isset($line['TotalAmount'])) {
            return (float) $line['TotalAmount'];
        }

        return (float) ($line['UnitPrice'] ?? 0) * (float) ($line['Quantity'] ?? 1);
    }
}
