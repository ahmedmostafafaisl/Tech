<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Services\Payment\TamaraWebhookException;
use App\Services\Payment\TamaraWebhookRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET / PUT|PATCH /api/tamara/webhook: read and update the webhook registered with Tamara.
 * Behind auth:sanctum + an admin role (routes/api.php). Nothing here can return, accept or change the secret.
 */
class TamaraWebhookManagementController extends Controller
{
    public function show(TamaraWebhookRegistrationService $service): JsonResponse
    {
        try {
            $result = $service->retrieve();
        } catch (TamaraWebhookException $e) {
            return $this->failure($e, $service);
        }

        return response()->json([
            'status' => true,
            'data'   => $result['webhook']->toSafeArray() + ['remote' => $result['remote']],
        ]);
    }

    public function update(Request $request, TamaraWebhookRegistrationService $service): JsonResponse
    {
        // The secret can never be set through the API. Rotating it = tamara:webhook:delete, then tamara:webhook:register.
        if ($request->hasAny(['headers', 'secret', 'authorization'])) {
            return response()->json([
                'status'  => false,
                'message' => 'The webhook secret cannot be changed through the API.',
            ], 422);
        }

        $validated = $request->validate([
            'url'      => ['sometimes', 'string', 'max:2048'],
            'events'   => ['sometimes', 'array', 'min:1', 'max:20'],
            'events.*' => ['string', 'regex:/^[a-z][a-z0-9_]{2,60}$/'],
        ]);

        if ($validated === []) {
            return response()->json(['status' => false, 'message' => 'Send url and/or events to update.'], 422);
        }

        try {
            $webhook = $service->update($validated);
        } catch (TamaraWebhookException $e) {
            return $this->failure($e, $service);
        }

        return response()->json(['status' => true, 'message' => 'Webhook updated.', 'data' => $webhook->toSafeArray()]);
    }

    private function failure(TamaraWebhookException $e, TamaraWebhookRegistrationService $service): JsonResponse
    {
        $status = match ($e->kind) {
            TamaraWebhookException::NOT_REGISTERED => 404,
            TamaraWebhookException::INVALID_INPUT  => 422,
            TamaraWebhookException::CONFIGURATION  => 500,
            default                                => $e->isNotFoundAtTamara() ? 404 : 502,
        };

        $body = ['status' => false, 'message' => $e->getMessage()];

        // when only Tamara is the problem, still show what we have locally (never the secret)
        if ($e->kind === TamaraWebhookException::API) {
            try {
                $local = $service->activeRecord();
                $body['local'] = $local?->toSafeArray();
            } catch (\Throwable) {
                // best effort only
            }
        }

        return response()->json($body, $status);
    }
}
