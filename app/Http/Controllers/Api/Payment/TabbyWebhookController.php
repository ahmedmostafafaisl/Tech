<?php

namespace App\Http\Controllers\Api\Payment;

use App\Jobs\ProcessTabbyWebhook;
use App\Models\TabbyWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TabbyWebhookController extends \App\Http\Controllers\Controller
{
    public function handle(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Verify HTTP method
        |--------------------------------------------------------------------------
        */

        if (!$request->isMethod('post')) {
            return response()->json([
                'status' => false,
                'message' => 'Method not allowed',
            ], 405);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Verify Tabby Auth Header
        |--------------------------------------------------------------------------
        */

        $headerName = config('tabby.webhook.header');
        $expectedSecret = config('tabby.webhook.secret');

        if (!$headerName || !$expectedSecret) {
            Log::critical('Tabby webhook credentials are not configured.');

            return response()->json([
                'status' => false,
                'message' => 'Webhook configuration error',
            ], 500);
        }

        $receivedSecret = $request->header($headerName);

        if (
            !$receivedSecret ||
            !hash_equals($expectedSecret, $receivedSecret)
        ) {
            Log::warning('Invalid Tabby webhook authentication.', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Get JSON payload
        |--------------------------------------------------------------------------
        */

        $payload = $request->json()->all();

        if (!is_array($payload) || empty($payload)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid payload',
            ], 400);
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Extract Tabby data
        |--------------------------------------------------------------------------
        */

        $tabbyPaymentId = $payload['id'] ?? null;

        $referenceId = data_get(
            $payload,
            'order.reference_id'
        );

        $status = strtolower(
            $payload['status'] ?? ''
        );

        if (!$tabbyPaymentId && !$referenceId) {
            Log::warning('Tabby webhook without payment/reference.', [
                'payload' => $payload,
            ]);

            /*
             * Return 200 so Tabby doesn't endlessly retry
             * an invalid notification.
             */
            return response()->json([
                'status' => true,
                'message' => 'Ignored',
            ], 200);
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Create unique event key
        |--------------------------------------------------------------------------
        |
        | Tabby does not provide a separate event_id in the documented
        | payload, so generate a deterministic fingerprint from the
        | important payload.
        |
        */

        $eventKey = hash(
            'sha256',
            json_encode(
                $payload,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            )
        );

        /*
        |--------------------------------------------------------------------------
        | 6. Deduplicate
        |--------------------------------------------------------------------------
        */

        $webhook = TabbyWebhook::firstOrCreate(
            [
                'event_key' => $eventKey,
            ],
            [
                'webhook_id' => $tabbyPaymentId,
                'payment_id' => $tabbyPaymentId,
                'reference_id' => $referenceId,
                'status' => $status,
                'is_test' => (bool) ($payload['is_test'] ?? false),
                'payload' => $payload,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 7. Already processed
        |--------------------------------------------------------------------------
        */

        if ($webhook->processed_at) {
            return response()->json([
                'status' => true,
                'message' => 'Already processed',
            ], 200);
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Queue processing
        |--------------------------------------------------------------------------
        |
        | Tabby recommends acknowledging quickly with HTTP 200.
        |
        */

        ProcessTabbyWebhook::dispatch($webhook->id);

        return response()->json([
            'status' => true,
            'message' => 'Webhook received',
        ], 200);
    }
}
