<?php

namespace App\Services\Payment;

/**
 * Guards against a discount covering the entire required_amount with no payment anywhere — not already
 * paid, not in the current request — unless DY365 itself shows some real payment activity already (a
 * non-zero PaidAmount) or a credit balance being applied (a non-zero used_balance). Without either, this
 * would be a 100%-off discount with nothing real behind it: silently zeroing an appointment's balance with
 * no payment and no DY365-side evidence it was ever actually settled.
 *
 * Extracted the same way DeliveryFeeThreshold was — a single, testable place for the rule, rather than
 * copy-pasted inline where it's used (NewDirectIntegrationController::sendPaymentLinks()).
 */
final class FullDiscountGuard
{
    /**
     * True when the request would cover required_amount entirely by discount, with no real payment at all,
     * and neither paidAmount nor usedBalance give it any backing.
     */
    public static function violates(
        float $requiredAmount,
        float $discount,
        float $paidTotal,
        float $newPaymentsTotal,
        float $paidAmount,
        float $usedBalance,
    ): bool {
        if ($requiredAmount <= 0) {
            return false;
        }

        $isFullDiscountNoRealPayment = $discount >= ($requiredAmount - 0.01)
            && ($paidTotal + $newPaymentsTotal) <= 0.01;

        return $isFullDiscountNoRealPayment && $paidAmount <= 0 && $usedBalance <= 0;
    }
}
