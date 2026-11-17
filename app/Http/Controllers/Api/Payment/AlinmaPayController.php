<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Models\DyPaymentLink;
use App\Services\Payment\AlinmaPayService;
use App\Services\Payment\DyPaymentLinkTransitions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AlinmaPay Payment Gateway — mirrors ClickPayController's "dy" flow (dyHandleReturn / dyHandleCallback /
 * verifyAndCompleteDyClickPayLink) as closely as the two gateways' protocols allow. AlinmaPay has exactly
 * one callback mechanism (the receiptUrl, Section 7), unlike ClickPay's separate return-redirect and
 * server-callback — so there is one verify-and-complete entry point here, not two.
 */
class AlinmaPayController extends Controller
{
    public function __construct(private readonly AlinmaPayService $alinmaPay) {}

    /**
     * Initiates a new AlinmaPay purchase for an appointment booking — Leg 1 of the spec's two-leg flow
     * (Section 4). Requires an authenticated session, same as ClickPayController::create() and
     * ClickPayController::refund() — this moves toward a real charge.
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'book_id'      => 'nullable|string',
            'order_id'     => 'required|string',
            'amount'       => 'required|numeric|min:0.01',
            'phone'        => 'nullable|string',
            'email'        => 'nullable|email',
            'custAccount'  => 'nullable|string',
            'description'  => 'nullable|string',
        ]);

        $result = $this->alinmaPay->createPayment(
            data: $validated,
            amount: (float) $validated['amount'],
            orderId: $validated['order_id'],
        );

        if (! $result['success']) {
            return response()->json(['status' => false, 'message' => $result['message'] ?? 'Payment initiation failed'], 422);
        }

        return response()->json([
            'status'       => true,
            'redirect_url' => $result['redirect_url'],
            'payment_id'   => $result['payment_id'],
        ]);
    }

    /**
     * The receiptUrl endpoint (Section 7) — AlinmaPay's one and only callback mechanism. Public: AlinmaPay
     * posts here directly, with no app auth token, exactly like ClickPay's /callback-dy and Tamara's
     * webhook. Always returns 200 (even on a verification failure) once the payload itself was readable —
     * there's nothing for AlinmaPay to usefully retry if the signature/amount genuinely don't match.
     */
    public function receipt(Request $request)
    {
        $encryptedData = $request->input('data');
        $termId        = $request->input('termId');

        if (empty($encryptedData)) {
            Log::warning('AlinmaPay receipt: missing data parameter', ['termId' => $termId]);

            return response()->json(['status' => false, 'message' => 'Missing data parameter'], 400);
        }

        try {
            $decrypted = $this->alinmaPay->decryptResponse($encryptedData);
            $payload   = json_decode($decrypted, true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Log::error('AlinmaPay receipt: could not decrypt/parse the response', ['error' => $e->getMessage()]);

            return response()->json(['status' => false, 'message' => 'Could not decrypt response'], 400);
        }

        $orderId = $payload['orderDetails']['orderId'] ?? null;

        if (empty($orderId)) {
            Log::warning('AlinmaPay receipt: decrypted payload has no orderDetails.orderId', ['payload' => $payload]);

            return response()->json(['status' => false, 'message' => 'Missing orderId in response'], 400);
        }

        $payment = DyPaymentLink::where('payment_method', 'alinmapay')
            ->where('dy_reference_id', $orderId)
            ->first();

        if (! $payment) {
            Log::warning('AlinmaPay receipt: local payment link not found.', ['order_id' => $orderId]);

            return response()->json(['status' => true, 'message' => 'Ignored — payment link not found']);
        }

        $result = $this->verifyAndCompleteAlinmaPayLink($payment, $payload);

        if ($result['outcome'] === 'unexpected_error') {
            return response()->json(['status' => false, 'message' => 'Payment verified but could not be recorded'], 500);
        }

        return response()->json(['status' => true, 'message' => 'Receipt processed', 'outcome' => $result['outcome']]);
    }

    /**
     * Validates the decrypted receipt payload (response code, amount, currency) and — only if everything
     * checks out — completes it via the shared DyPaymentLinkTransitions::complete(), exactly like
     * ClickPayController::verifyAndCompleteDyClickPayLink() does for ClickPay. The local update happens
     * BEFORE notifying DY365, for the same reason documented there: AlinmaPay's own accepted response is
     * the source of truth for whether the customer paid, not DY365's acknowledgement of being told about it.
     *
     * @return array{outcome: string, payment: DyPaymentLink} outcome is one of:
     *   'paid'             — this call (or an earlier one) confirmed and recorded it
     *   'failed'           — AlinmaPay's response code was not a success code, or didn't match this link
     *   'unexpected_error' — a genuinely unexpected failure recording it locally
     */
    private function verifyAndCompleteAlinmaPayLink(DyPaymentLink $payment, array $decryptedPayload): array
    {
        // A replay of a payment that is already complete: nothing to verify, nothing to tell DY365 again.
        if (in_array($payment->status, ['paid', 'success'], true)) {
            return ['outcome' => 'paid', 'payment' => $payment];
        }

        // "000" and "001" are AlinmaPay's own success codes (Section 14: "000 Transaction Successful",
        // "001 Transaction Approved" — both appear across the spec's own sample responses for an accepted
        // transaction). Anything else is not a successful payment.
        $responseCode = (string) ($decryptedPayload['responseCode'] ?? '');

        if (! in_array($responseCode, ['000', '001'], true)) {
            if (in_array($payment->status, DyPaymentLinkTransitions::OPEN_STATES, true)) {
                $payment->update(['status' => 'failed']);
            }

            Log::warning('AlinmaPay receipt: non-success response code; link marked failed', [
                'dy_reference_id' => $payment->dy_reference_id,
                'response_code'   => $responseCode,
            ]);

            return ['outcome' => 'failed', 'payment' => $payment->fresh()];
        }

        // Successful code: it must also be the amount this link was created for (AlinmaPay's receipt
        // payload doesn't echo the currency back, so only amount is checked here, same caution
        // ClickPayController applies to cart_amount/cart_currency).
        $charged = $decryptedPayload['amountDetails']['amount'] ?? null;

        if ($charged !== null && (! is_numeric($charged) || abs((float) $charged - (float) $payment->amount) >= 0.005)) {
            Log::warning('AlinmaPay receipt: response amount does not match the link amount; link left unchanged', [
                'dy_reference_id' => $payment->dy_reference_id,
                'charged'         => $charged,
                'expected'        => $payment->amount,
            ]);

            return ['outcome' => 'failed', 'payment' => $payment];
        }

        try {
            $result = app(DyPaymentLinkTransitions::class)
                ->complete($payment, ['status' => 'paid'], 'Approved');
        } catch (\Throwable $e) {
            Log::error('AlinmaPay receipt: unexpected failure completing the payment locally', [
                'dy_reference_id' => $payment->dy_reference_id,
                'error'           => $e->getMessage(),
            ]);

            return ['outcome' => 'unexpected_error', 'payment' => $payment];
        }

        return ['outcome' => in_array($result, ['done', 'done_dy_notification_failed', 'already'], true) ? 'paid' : 'failed', 'payment' => $payment->fresh()];
    }
}
