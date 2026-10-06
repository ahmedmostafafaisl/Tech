<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AddDefaultOtpPhonesRequest;
use App\Services\Auth\DefaultOtpAllowlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * POST /api/settings/otp-default/phones
 *
 * Adds phone numbers to the default-OTP allowlist. Additive and idempotent: it never removes a number, never changes
 * the code, and never turns the switch on. Behind auth:sanctum + role:super_admin (routes/api.php) — stricter than the
 * usual admin, because each number added here is one that will accept a fixed OTP once the switch is on.
 */
class DefaultOtpAllowlistController extends Controller
{
    public function store(AddDefaultOtpPhonesRequest $request, DefaultOtpAllowlist $allowlist): JsonResponse
    {
        try {
            $result = $allowlist->add($request->validated()['phones']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }

        // Who added which numbers is logged (only when something actually changed); the code itself never is.
        if ($result['added'] !== []) {
            Log::warning('Default OTP allowlist: numbers added', [
                'added'       => $result['added'],
                'by_user_id'  => Auth::id(),
                'by_email'    => Auth::user()?->email,
                'environment' => app()->environment(),
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => $result['added'] === []
                ? 'No new numbers: every given number was already on the allowlist.'
                : count($result['added']) . ' number(s) added to the default OTP allowlist.',
            'data' => [
                'added'           => $result['added'],
                'already_present' => $result['already_present'],
                'allowlist'       => $result['allowlist'],
                'allowlist_count' => count($result['allowlist']),
            ],
        ]);
    }
}
