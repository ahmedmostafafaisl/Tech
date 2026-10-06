<?php

namespace App\Services\Payment;

use App\Models\DyPaymentLink;
use Illuminate\Support\Facades\Log;

/**
 * Asks Tamara what really happened to the order behind ONE local DY payment link.
 *
 * It only ever uses the Tamara order id STORED on the link when the checkout was created. A value that arrives in a
 * browser redirect or a webhook is only compared with it (a mismatch refuses the request) and is never used to call
 * Tamara. The decision itself is TamaraOrderVerification's. Extracted unchanged from DyController (Patch A2) so the
 * redirect and the webhook share one implementation.
 */
final class TamaraDyLinkVerifier
{
    public function __construct(private readonly TamaraService $tamara)
    {
    }

    /**
     * One read-only check of the order at Tamara.
     *
     * @return array{verdict: string, reason: string}
     */
    public function read(DyPaymentLink $link, ?string $claimedOrderId = null): array
    {
        $stored  = trim((string) $link->payment_reference_id);
        $claimed = trim((string) $claimedOrderId);

        if ($stored === '') {
            return ['verdict' => TamaraOrderVerification::MISMATCH, 'reason' => 'the link has no stored Tamara order id'];
        }

        if ($claimed !== '' && ! hash_equals($stored, $claimed)) {
            return ['verdict' => TamaraOrderVerification::MISMATCH, 'reason' => 'order id differs from the one created for this link'];
        }

        return TamaraOrderVerification::evaluate(
            $this->tamara->getOrderStatus($stored),
            (string) $link->dy_reference_id,
            $link->amount,
            'SAR',
            $stored
        );
    }

    /**
     * Verify, and where Tamara says the order is approved, authorise / capture it (best effort, as before).
     *
     * @return array{outcome: string, payment_id?: string, reason?: string}  outcome: approved | mismatch | error | rejected | pending
     */
    public function verify(DyPaymentLink $link, ?string $claimedOrderId = null): array
    {
        $check = $this->read($link, $claimedOrderId);

        switch ($check['verdict']) {
            case TamaraOrderVerification::ERROR:
                return ['outcome' => 'error', 'reason' => $check['reason']];
            case TamaraOrderVerification::MISMATCH:
                return ['outcome' => 'mismatch', 'reason' => $check['reason']];
            case TamaraOrderVerification::REJECTED:
                return ['outcome' => 'rejected'];
            case TamaraOrderVerification::PENDING:
                return ['outcome' => 'pending'];
        }

        $orderId = trim((string) $link->payment_reference_id);

        if ($check['verdict'] === TamaraOrderVerification::NEEDS_AUTHORISE) {
            $auth       = $this->tamara->authorizeOrder($orderId);
            $authorised = is_array($auth) && empty($auth['error']) && in_array(strtolower((string) ($auth['status'] ?? '')), ['authorised', 'authorized'], true);

            if (! $authorised) {
                // e.g. the webhook authorised it a moment ago: trust Tamara's current word, not the failed call
                $after = $this->read($link, $claimedOrderId)['verdict'];

                if (! in_array($after, [TamaraOrderVerification::AUTHORISED, TamaraOrderVerification::CAPTURED], true)) {
                    return ['outcome' => 'error', 'reason' => 'Tamara did not authorise the order'];
                }

                $check['verdict'] = $after;
            } else {
                $check['verdict'] = TamaraOrderVerification::AUTHORISED;
            }
        }

        if ($check['verdict'] === TamaraOrderVerification::AUTHORISED) {
            // Best effort, exactly as before: its result has never gated the link, and it is not made to now.
            $capture = $this->tamara->captureOrderDy((string) $link->dy_reference_id, $orderId);

            if (is_array($capture) && ! empty($capture['error'])) {
                Log::warning('DY Tamara capture reported an error', ['dy_reference_id' => $link->dy_reference_id, 'error' => $capture['message'] ?? null]);
            }
        }

        return ['outcome' => 'approved', 'payment_id' => $orderId];
    }

    /** Is this link's order paid at Tamara? 'paid' | 'not_paid' | 'unknown'. */
    public function standing(DyPaymentLink $link): string
    {
        return match ($this->read($link)['verdict']) {
            TamaraOrderVerification::NEEDS_AUTHORISE, TamaraOrderVerification::AUTHORISED, TamaraOrderVerification::CAPTURED => 'paid',
            TamaraOrderVerification::PENDING, TamaraOrderVerification::REJECTED                                              => 'not_paid',
            default                                                                                                          => 'unknown',
        };
    }
}
