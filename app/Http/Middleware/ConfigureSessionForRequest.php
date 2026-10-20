<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aligns session cookie "secure" flag with the actual request scheme.
 * Fixes 419 / "page expired" when SESSION_SECURE_COOKIE=true but the site is opened over HTTP.
 */
class ConfigureSessionForRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('crm.session_secure_auto', true)) {
            config(['session.secure' => $request->isSecure()]);
        }

        return $next($request);
    }
}
