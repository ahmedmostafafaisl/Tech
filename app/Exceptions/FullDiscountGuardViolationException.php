<?php

namespace App\Exceptions;

/**
 * Thrown — never just returned — from inside sendPaymentLinks()'s DB::transaction() closure when
 * FullDiscountGuard rejects a request. A plain `return response()->json(..., 400)` from inside a
 * transaction closure does NOT roll anything back — DB::transaction() only rolls back on a thrown
 * exception, so a return there would leave every write already made earlier in that same transaction
 * (firstOrCreate()'s initial insert, the discount/required_amount saves) committed anyway, even though the
 * request was supposed to be rejected outright. Confirmed in production: a rejected request still ended up
 * stored with discount=100 and status="paid".
 *
 * $details carries the exact body the 400 response needs (required_amount, discount, paid_amount,
 * used_balance) so the catch outside the transaction can build the same response without needing to
 * recompute anything.
 */
class FullDiscountGuardViolationException extends \RuntimeException
{
    public function __construct(public readonly array $details)
    {
        parent::__construct('Full discount with no real payment rejected — PaidAmount and used_balance are both zero.');
    }
}
