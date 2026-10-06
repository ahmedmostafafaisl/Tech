<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Services\Payment\TamaraWebhookProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** POST /api/tamara/webhook: the stable endpoint the webhook registered with Tamara points at. Public; see TamaraWebhookProcessor. */
class TamaraWebhookController extends Controller
{
    public function __invoke(Request $request, TamaraWebhookProcessor $processor): JsonResponse
    {
        [$status, $body] = $processor->handle($request);

        return response()->json($body, $status);
    }
}
