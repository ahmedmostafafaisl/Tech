<?php

namespace App\Services\Payment;

/**
 * Single source of truth for the "new" required_amount calculation:
 *
 *   remaining = required_amount - paid_amount
 *   if (remaining > 0) { remaining = max(0, remaining - used_balance) }
 *
 * used_balance is only ever applied when there's still something owed
 * after subtracting paid_amount — and it can never push the result
 * negative. If paid_amount alone already covers required_amount,
 * used_balance is not touched at all.
 *
 * This exists so every place that eventually needs this calculation
 * (sendPaymentLinks now; completeAppointment, checkPaymentStatus, the
 * payment-gateway reconciliation services, PaymentCompletionDispatcher,
 * and the audit/reminder commands in later steps) shares one
 * implementation instead of each re-deriving the formula.
 *
 * Gated behind the 'new_required_amount_calculation_active' feature
 * flag (see Setting::isActive()) at each call site — this class itself
 * has no knowledge of the flag, it's just the calculation.
 */
class RequiredAmountCalculator
{
    public function calculate(float $requiredAmount, float $paidAmount, float $usedBalance): float
    {
        $remaining = max(0, $requiredAmount - $paidAmount);

        if ($remaining > 0) {
            $remaining = max(0, $remaining - $usedBalance);
        }

        return $remaining;
    }

    public function calculateUsedBalanceApplied(float $requiredAmount, float $paidAmount, float $usedBalance): float
    {
        $remaining = max(0, $requiredAmount - $paidAmount);

        if ($remaining > 0) {
            return min($usedBalance, $remaining);
        }

        return 0.0;
    }
}
