<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiBearerAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $bearerToken = $request->bearerToken();

        $configuredToken = config('auth.api_bearer');
        if (! is_string($configuredToken) || $configuredToken === '' || ! is_string($bearerToken) || ! hash_equals($configuredToken, $bearerToken)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
