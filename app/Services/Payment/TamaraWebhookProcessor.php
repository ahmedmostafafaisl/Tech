<?php

namespace App\Services\Payment;

use App\Models\DyPaymentLink;
use App\Models\TamaraWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handles a Tamara notification for a DY payment link. Serves BOTH endpoints (the stable POST /api/tamara/webhook and
 * the per-checkout URL older links still carry), so there is one implementation.
 *
 * THE WEBHOOK IS A TRIGGER, NEVER A SOURCE OF TRUTH.
 *
 *   1. Authentication (the generated secret echoed in the Authorization header, or an optional signed JWT) is only
 *      recorded. Missing or invalid authentication does NOT reject the request and does NOT change what happens next:
 *      the notification is simply treated as untrusted. Both paths are verified with Tamara's API.
 *   2. The payload can only point at a LOCAL dy_payment_links row (by order_reference_id, or by order_id). It cannot
 *      select Tamara data: its order_id is only compared with the order id stored on that row (a mismatch refuses the
 *      request before any call to Tamara), and Tamara is asked about the STORED order id.
 *   3. TamaraOrderVerification decides: same order, same reference, same amount, same currency, and a status. The
 *      event name is only logged; "approved / captured" in a payload proves nothing.
 *   4. Only then does the link change state and DY365 hear "Approved" (once, with the existing acknowledgement rules).
 *
 * Every failure to verify is fail-closed: no state change, no DY365 notification, and a retryable 503 when the cause may
 * pass (Tamara or DY365 unavailable).
 */
final class TamaraWebhookProcessor
{
    public function __construct(
        private readonly TamaraDyLinkVerifier $verifier,
        private readonly DyPaymentLinkTransitions $transitions,
    ) {}

    /**
     * @param  string|null  $urlReference  the PAY-... reference in the old per-checkout URL (null on the stable endpoint)
     * @return array{0: int, 1: array<string, mixed>}  [HTTP status, JSON body]
     */
    public function handle(Request $request, ?string $urlReference = null): array
    {
        $legacyUrl      = $urlReference !== null;
        $authentication = $this->authentication($request);
        $context        = [
            'tamara_webhook_verification_mode' => in_array($authentication, ['secret', 'jwt'], true) ? 'authenticated' : 'api_fallback',
            'tamara_webhook_authentication'    => $authentication,
            'event_type'                       => $this->text($request->input('event_type'), 60),
            'endpoint'                         => $legacyUrl ? 'legacy_url' : 'stable',
        ];

        Log::info('Tamara webhook received', $context);

        $claimedOrderId   = $this->text($request->input('order_id'));
        $claimedReference = $this->text($request->input('order_reference_id'));
        $urlReference     = $this->text($urlReference);

        if ($claimedReference !== '' && $urlReference !== '' && ! hash_equals($urlReference, $claimedReference)) {
            return $this->refuse($context, 422, 'The reference in the notification does not match the URL it was sent to.', 'reference_mismatch');
        }

        $reference = $claimedReference !== '' ? $claimedReference : $urlReference;

        if ($claimedOrderId === '') {
            return $this->refuse($context, 400, 'Missing required fields.', 'missing_fields');
        }

        // The payload may only point at a LOCAL row.
        $links = DyPaymentLink::where('payment_method', 'tamara');
        $link  = $reference !== ''
            ? $links->where('dy_reference_id', $reference)->first()
            : $links->where('payment_reference_id', $claimedOrderId)->first();

        if (! $link) {
            Log::warning('Tamara webhook did not match a DY payment link', $context + ['reference' => $reference]);

            // The old URL names one link, so "not found" is an error. The stable endpoint receives every event of the
            // merchant account (including other flows), so an unknown reference is acknowledged and ignored.
            return $legacyUrl
                ? [404, ['status' => 'error', 'message' => 'Payment not found.']]
                : [200, ['status' => 'ignored', 'message' => 'No matching DY payment link.']];
        }

        $context['dy_reference_id'] = $link->dy_reference_id;
        $stored                     = trim((string) $link->payment_reference_id);

        // The notification's order id is a claim to compare, never a value to use: refuse BEFORE any call to Tamara.
        if ($stored === '' || ! hash_equals($stored, $claimedOrderId)) {
            return $this->refuse($context, 422, 'The order id does not match this payment link.', 'order_mismatch');
        }

        if ($link->status === 'success') {
            return $this->done($context, [200, ['status' => 'ok', 'message' => 'Already processed.']], 'already_processed');
        }

        try {
            $verified = $this->verifier->verify($link, $claimedOrderId);
        } catch (\Throwable $e) {
            Log::error('Tamara webhook: verification with Tamara failed; link left unchanged', $context + ['error' => $e->getMessage()]);

            return [503, ['status' => 'error', 'message' => 'The order could not be verified with Tamara. The notification can be retried.']];
        }

        return match ($verified['outcome']) {
            'approved' => $this->transition($link, ['status' => 'success', 'payment_id' => $verified['payment_id']], 'Approved', $context),
            'rejected' => $this->transition($link, ['status' => 'failed'], 'Declined', $context),
            'pending'  => $this->done($context, [202, ['status' => 'ok', 'message' => 'The order is not approved at Tamara (yet); nothing changed.']], 'pending'),
            'mismatch' => $this->refuse($context, 422, 'The Tamara order does not match this payment link.', 'tamara_mismatch', $verified['reason'] ?? null),
            default    => $this->unverifiable($context, $verified['reason'] ?? null),
        };
    }

    // ------------------------------------------------------------------ authentication (recorded, never enforced)

    /** @return string secret | jwt | invalid | missing */
    private function authentication(Request $request): string
    {
        $header = trim((string) $request->header('Authorization', ''));
        $secret = $this->activeSecret();

        if ($secret !== null && $header !== '' && hash_equals($secret, $header)) {
            return 'secret';
        }

        // Optional: Tamara's signed JWT, when TAMARA_NOTIFICATION_TOKEN is configured.
        $notificationToken = (string) config('services.tamara.notification_token');
        $candidates        = TamaraWebhookSignature::candidatesFrom($request);

        if ($notificationToken !== '' && TamaraWebhookSignature::authenticate($candidates, $notificationToken)) {
            return 'jwt';
        }

        return ($header === '' && $candidates === []) ? 'missing' : 'invalid';
    }

    private function activeSecret(): ?string
    {
        try {
            $host = strtolower((string) parse_url((string) config('services.tamara.api_url'), PHP_URL_HOST));

            return TamaraWebhook::activeFor((string) app()->environment(), $host)->latest('id')->first()?->secretValue();
        } catch (\Throwable) {
            return null;   // no table / unreadable: simply not authenticated
        }
    }

    // ------------------------------------------------------------------ outcomes

    private function transition(DyPaymentLink $link, array $update, string $dyStatus, array $context): array
    {
        try {
            $result = $this->transitions->complete($link, $update, $dyStatus);
        } catch (\Throwable $e) {
            // Log::error('Tamara webhook: DY365 did not accept the notification; link left unchanged', $context + ['error' => $e->getMessage()]);

            return [503, ['status' => 'error', 'message' => 'DY365 could not be notified. The notification can be retried.']];
        }

        return match ($result) {
            'done'    => $this->done($context, [200, ['status' => 'ok']], $update['status']),
            'already' => $this->done($context, [200, ['status' => 'ok', 'message' => 'Already processed.']], 'already_processed'),
            default   => $this->refuse($context, 409, 'This payment link can no longer be changed.', 'invalid_state'),
        };
    }

    private function unverifiable(array $context, ?string $reason): array
    {
        Log::warning('Tamara webhook: the order could not be verified; link left unchanged', $context + ['reason' => $reason]);

        return [503, ['status' => 'error', 'message' => 'The order could not be verified with Tamara. The notification can be retried.']];
    }

    private function refuse(array $context, int $status, string $message, string $result, ?string $reason = null): array
    {
        Log::warning('Tamara webhook refused', $context + ['result' => $result, 'reason' => $reason]);

        return [$status, ['status' => 'error', 'message' => $message]];
    }

    /** @param array{0: int, 1: array<string, mixed>} $response */
    private function done(array $context, array $response, string $result): array
    {
        Log::info('Tamara webhook processed', $context + ['result' => $result]);

        return $response;
    }

    private function text(mixed $value, int $max = 191): string
    {
        return is_scalar($value) ? mb_substr(trim((string) $value), 0, $max) : '';
    }
}
