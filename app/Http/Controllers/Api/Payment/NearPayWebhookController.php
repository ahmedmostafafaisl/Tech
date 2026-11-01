<?php

namespace App\Http\Controllers\Api\Payment;

use App\Jobs\ProcessNearPayWebhook;
use App\Models\NearPayWebhook;
use App\Services\Telegram\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * NearPay's webhook documentation does not define ANY authentication for this endpoint — no signature, no
 * shared secret header, no documented sender IP range (confirmed by reading the full page: no mention of
 * "signature", "secret", "HMAC", or any X- header anywhere). This is not an oversight here; there is
 * nothing to check. The endpoint is therefore, necessarily, unauthenticated at the HTTP layer — which is
 * why NOTHING here ever marks a payment paid, refunded, or failed directly from this payload. It only
 * stores what arrived and queues a job that re-verifies against NearPay's own Banks API before acting on
 * anything (the same rule Tabby's webhook already follows). Rate limiting (routes/api.php) is the one
 * defence-in-depth measure available for the missing signature; see the integration notes for why that is
 * an acceptable, documented trade-off rather than an invented one.
 *
 * Also per the docs: a refund is NOT its own event — it arrives as a normal "Transaction Approved" payload
 * with is_refunded: true on it. Non-transaction events (terminal/device/admin activity) share no fields
 * with a transaction payload at all, so they are told apart by shape, not by an explicit event-type field
 * (none is documented) — anything that is not recognisably a transaction is acknowledged and ignored.
 */
class NearPayWebhookController extends \App\Http\Controllers\Controller
{
    public function handle(Request $request)
    {
        if (!$request->isMethod('post')) {
            return response()->json(['status' => false, 'message' => 'Method not allowed'], 405);
        }

        $payload = $request->json()->all();
        if (!is_array($payload) || empty($payload)) {
            return response()->json(['status' => false, 'message' => 'Invalid payload'], 400);
        }

        $data = $payload['payload'] ?? null;
        if (!is_array($data)) {
            // Acknowledge so NearPay doesn't retry a shape it will never send differently.
            return response()->json(['status' => true, 'message' => 'Ignored'], 200);
        }

        if (!$this->looksLikeTransactionEvent($data)) {
            // A real, documented event (terminal reconciled, device connected, admin created, ...) that
            // this integration has no use for. Acknowledged, never stored or queued.
            return response()->json(['status' => true, 'message' => 'Ignored'], 200);
        }

        $transactionId = $data['transaction_uuid'] ?? $data['id'] ?? null;
        if (!$transactionId) {
            return response()->json(['status' => true, 'message' => 'Ignored'], 200);
        }

        // No event-id field is documented, so — same as TabbyWebhookController — the dedup key is a
        // fingerprint of the payload itself.
        $eventKey = hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $webhook = NearPayWebhook::firstOrCreate(
            ['event_key' => $eventKey],
            [
                'transaction_id'             => $transactionId,
                // Populated with this app's own DirectAppointmentPayment.reference_id once a real device
                // reports it — null in NearPay's own documentation examples, but a far more direct match
                // than transaction_id/payment_id alone (see ProcessNearPayWebhook).
                'customer_reference_number'  => $data['customer_reference_number'] ?? null,
                'is_approved'    => $data['is_approved'] ?? null,
                'is_refunded'    => $data['is_refunded'] ?? null,
                'is_reversed'    => $data['is_reversed'] ?? null,
                'payload'        => $payload,
            ]
        );

        if ($webhook->processed_at) {
            return response()->json(['status' => true, 'message' => 'Already processed'], 200);
        }

        ProcessNearPayWebhook::dispatch($webhook->id);

        return response()->json(['status' => true, 'message' => 'Webhook received'], 200);
    }

    /**
     * No event-type field is documented on the payload, so a transaction event is recognised by the fields
     * that only ever appear together on one, confirmed against real Approved/Rejected/Reversed webhook
     * deliveries: is_approved, status_message and amount_authorized are present on every one of the three,
     * regardless of outcome — unlike card_scheme or merchant, which this webhook's actual payloads never
     * carry at all (those only ever appear on GET /transactions/{id}'s own receipt shape, a different,
     * separate response this webhook does not reuse).
     */
    private function looksLikeTransactionEvent(array $data): bool
    {
        return array_key_exists('is_approved', $data)
            && array_key_exists('status_message', $data)
            && array_key_exists('amount_authorized', $data);
    }
}
