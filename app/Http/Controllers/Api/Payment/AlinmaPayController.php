<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Models\DirectAppointmentPayment;
use App\Models\DyPaymentLink;
use App\Services\Payment\AlinmaPayPaymentSyncService;
use App\Services\Payment\AlinmaPayService;
use App\Services\Payment\DyPaymentLinkTransitions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AlinmaPay Payment Gateway. Two entirely separate flows live here, exactly as ClickPayController keeps its
 * "dy" flow and its "new"/DirectAppointmentPayment flow side by side with no shared state between them:
 *   - create() / receipt(): the DyPaymentLink flow (Section 4 Leg 1 + Section 7 receiptUrl), mirroring
 *     ClickPayController's dyHandleReturn/dyHandleCallback/verifyAndCompleteDyClickPayLink.
 *   - newHandleCallback(): the DirectAppointmentPayment ("send payment links") flow, mirroring
 *     ClickPayController::newHandleCallback() + ClickPayPaymentSyncService. Initiation itself
 *     (AlinmaPayService::createInvoiceNew()) is called directly from
 *     NewDirectIntegrationController::sendPaymentLinks()'s own payment-type dispatch, the same way
 *     ClickPayService::createInvoiceNew() already is — there is no separate "create" action for this flow.
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

    /**
     * The receiptUrl for the DirectAppointmentPayment ("send payment links") flow — the URL
     * AlinmaPayService::createInvoiceNew() sets. Public, same reasoning as receipt() above. Mirrors
     * ClickPayController::newHandleCallback(): looks the local row up by reference_id/payment_id, then
     * delegates all status-interpreting/DB-updating logic to AlinmaPayPaymentSyncService::syncPayment() —
     * which re-queries AlinmaPay directly rather than trusting this callback's own decrypted body, so a
     * manual/polling sync endpoint can share the exact same logic later if one is added.
     */
    /**
     * The customer's browser lands here after completing (or abandoning) payment on AlinmaPay's hosted
     * page — not a pure server-to-server webhook — so, on every outcome, this renders the same
     * Payment.result blade view ClickPayController::newHandleReturn() already uses for its own browser
     * return, rather than JSON a person would otherwise be left looking at raw.
     */
    public function newHandleCallback(Request $request, AlinmaPayPaymentSyncService $syncService)
    {
        $referenceId = $request->input('reference_id');
        $bookId      = $request->input('book_id');

        Log::info('AlinmaPay callback received.', ['payload' => $request->all()]);

        if (! $referenceId) {
            return response()->json(['status' => false, 'message' => 'Missing reference_id'], 400);
        }

        $payment = DirectAppointmentPayment::where('reference_id', $referenceId)->first();

        if (! $payment) {
            Log::warning('AlinmaPay callback: local payment not found.', [
                'reference_id' => $referenceId,
                'book_id'      => $bookId,
            ]);

            return view('Payment.result', [
                'status'          => 'failed',
                'payment_type'    => 'alinmapay',
                'message'         => 'Payment not found',
                'payment'         => null,
                'priceWithoutTax' => 0,
                'taxAmount'       => 0,
            ]);
        }

        try {
            $syncService->syncPayment($payment);
        } catch (\Throwable $e) {
            Log::error('AlinmaPay callback sync failed.', [
                'reference_id' => $payment->reference_id,
                'book_id'      => $bookId,
                'error'        => $e->getMessage(),
            ]);
            // $payment is left exactly as syncPayment() found it — no attempt here to guess a status.
        }

        $payment->refresh();

        $totalAmount     = (float) ($payment->price ?? 0);
        $priceWithoutTax = round($totalAmount / 1.15, 2);
        $taxAmount       = round($totalAmount - $priceWithoutTax, 2);

        return view('Payment.result', [
            'status'          => $payment->status,
            'payment_type'    => 'alinmapay',
            'payment'         => $payment,
            'priceWithoutTax' => $priceWithoutTax,
            'taxAmount'       => $taxAmount,
        ]);
    }

    /**
     * GET /integration/alinmapay/payment-details?reference_id=...
     * GET /integration/alinmapay/payment-details?payment_id=...
     * Mirrors ClickPayController::getPaymentDetails() — the local DirectAppointmentPayment row alongside
     * AlinmaPay's own, live transaction data (via inquiry()), not one or the other.
     */
    public function getPaymentDetails(Request $request)
    {
        $request->validate([
            'payment_id'   => 'nullable|string|required_without:reference_id',
            'reference_id' => 'nullable|string|required_without:payment_id',
        ]);

        if ($request->filled('reference_id')) {
            $localPayment = DirectAppointmentPayment::where('reference_id', $request->input('reference_id'))->first();
        } else {
            $localPayment = DirectAppointmentPayment::where('payment_id', $request->input('payment_id'))
                ->orWhere('gateway_order_id', $request->input('payment_id'))
                ->first();
        }

        if (! $localPayment) {
            $identifier = $request->input('reference_id') ?? $request->input('payment_id');

            return response()->json([
                'status'  => false,
                'message' => "No local payment found for '{$identifier}'.",
            ], 404);
        }

        // gateway_order_id/reference_id for inquiry()'s order.orderId and signature (whichever this
        // transaction was actually created with — see AlinmaPayPaymentSyncService::syncPayment()'s own
        // identical fallback); payment_id, separately, for inquiry()'s $referenceId — AlinmaPay's OWN
        // transaction id, never our own order.orderId (see AlinmaPayService::inquiry()'s own docblock for
        // why: confirmed in production as "Invalid tran message id" otherwise).
        $orderId = $localPayment->gateway_order_id ?: $localPayment->reference_id;

        if (! $orderId) {
            return response()->json([
                'status'  => false,
                'message' => 'Local payment has no gateway_order_id or reference_id to query AlinmaPay with.',
            ], 422);
        }

        if (! $localPayment->payment_id) {
            return response()->json([
                'status'  => false,
                'message' => 'Local payment has no payment_id (AlinmaPay transaction id) to query AlinmaPay with.',
            ], 422);
        }

        try {
            $alinmapayData = $this->alinmaPay->inquiry($localPayment->payment_id, $orderId, (float) $localPayment->price);
        } catch (\Throwable $e) {
            Log::error('Failed to query AlinmaPay payment.', ['order_id' => $orderId, 'error' => $e->getMessage()]);

            return response()->json([
                'status'  => false,
                'message' => 'Failed to retrieve payment from AlinmaPay.',
                'error'   => $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'status' => true,
            'data'   => [
                'alinmapay_payment' => $alinmapayData,
                'local_payment'     => $localPayment,
            ],
        ]);
    }

    /**
     * GET /integration/alinmapay/sync-payment-status?reference_id=...
     * GET /integration/alinmapay/sync-payment-status?payment_id=...
     * Mirrors ClickPayController::syncPaymentStatus() — same AlinmaPayPaymentSyncService::syncPayment() the
     * receipt callback itself uses, so a manual check and the automatic one can never disagree on what
     * "synced" means.
     */
    public function syncPaymentStatus(Request $request, AlinmaPayPaymentSyncService $syncService)
    {
        $request->validate([
            'payment_id'   => 'nullable|string|required_without:reference_id',
            'reference_id' => 'nullable|string|required_without:payment_id',
        ]);

        if ($request->filled('reference_id')) {
            $payment = DirectAppointmentPayment::where('reference_id', $request->input('reference_id'))->first();
        } else {
            $payment = DirectAppointmentPayment::where('payment_id', $request->input('payment_id'))
                ->orWhere('gateway_order_id', $request->input('payment_id'))
                ->first();
        }

        if (! $payment) {
            return response()->json(['status' => false, 'message' => 'No local payment found for the given identifier.'], 404);
        }

        try {
            $result = $syncService->syncPayment($payment);
        } catch (\Throwable $e) {
            Log::error('Manual AlinmaPay payment sync failed.', [
                'payment_id'   => $payment->payment_id,
                'reference_id' => $payment->reference_id,
                'error'        => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Failed to sync payment status.',
                'error'   => $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'status' => true,
            'data'   => [
                'reference_id' => $payment->reference_id,
                'payment_id'   => $payment->payment_id,
                ...$result,
            ],
        ]);
    }
}
