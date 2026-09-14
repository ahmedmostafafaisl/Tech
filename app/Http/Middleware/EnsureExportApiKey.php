<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the export download route. Requires header:
 *   X-Export-Api-Key: <value matching EXPORT_API_KEY in .env>
 *
 * Uses hash_equals() for constant-time comparison — same pattern as the
 * Emergency Mode System's secret check elsewhere in this app.
 */
class EnsureExportApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $provided = (string) $request->header('X-Export-Api-Key', '');
        $configured = (string) config('services.export.api_key', '');

        if ($configured === '' || !hash_equals($configured, $provided)) {
            Log::warning('Unauthorized export download attempt', [
                'ip'   => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json(['status' => false, 'message' => 'Forbidden.'], 403);
        }

        return $next($request);
    }
}
