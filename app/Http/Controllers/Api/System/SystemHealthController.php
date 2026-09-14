<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SystemHealthController extends Controller
{
    /**
     * GET /admin/system/config-health
     *
     * Reports which required configuration keys are set, WITHOUT ever
     * exposing their actual values — only whether each is present and
     * its length (useful for spotting an accidentally-empty string).
     * Restricted to super_admin: even knowing which keys are missing is
     * information worth protecting.
     */
    public function configHealth(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('super_admin')) {
            return response()->json(['status' => false, 'message' => 'Forbidden.'], 403);
        }

        return   $checks = [
            'dy365.client_id'      => config('services.dy365.client_id'),
            'dy365.client_secret'  => config('services.dy365.client_secret'),
            'tabby.secret_key'     => config('services.tabby.secret_key'),
            'tabby.public_key'     => config('services.tabby.public_key'),
            'tabby.merchant_code'  => config('services.tabby.merchant_code'),
            'tabby.webhook.secret' => config('services.tabby.webhook.secret'),
            'tamara.api_key'       => config('services.tamara.api_key'),
            'clickpay.server_key'  => config('services.clickpay.server_key'),
            'clickpay.profile_id'  => config('services.clickpay.profile_id'),
            'powerbi.username'     => config('services.powerbi.username'),
            'powerbi.password'     => config('services.powerbi.password'),
            'whatsapp.token'       => config('services.whatsapp.token'),
            'whatsapp.verify_token' => config('services.whatsapp.verify_token'),
            'export.api_key'       => config('services.export.api_key'),
            'fcm.credentials'      => config('services.fcm.credentials'),
            'telegram.bot_token'   => config('services.telegram.bot_token'),
            'taqnyat.api_key'      => config('services.taqnyat.api_key'),
            'database.password'   => config('database.connections.mysql.password'),
        ];

        $results = collect($checks)->map(function ($value) {
            return [
                'set'    => !empty($value),
                // Length only, never the value itself.
                'length' => $value ? strlen((string) $value) : 0,
            ];
        });

        $missing = $results->filter(fn($r) => !$r['set'])->keys()->values();

        return response()->json([
            'status'  => $missing->isEmpty(),
            'missing' => $missing,
            'checks'  => $results,
        ]);
    }
}
