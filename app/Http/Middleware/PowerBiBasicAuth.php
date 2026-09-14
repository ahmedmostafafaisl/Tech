<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PowerBiBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $username = (string) $request->getUser();
        $password = (string) $request->getPassword();
        $validUsername = (string) config('services.powerbi.username');
        $validPassword = (string) config('services.powerbi.password');

        if (
            $validUsername === '' || $validPassword === '' ||
            ! hash_equals($validUsername, $username) ||
            ! hash_equals($validPassword, $password)
        ) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="Power BI Export"',
            ]);
        }

        return $next($request);
    }
}
