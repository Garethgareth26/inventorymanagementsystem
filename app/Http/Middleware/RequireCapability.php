<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireCapability
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $capability): Response
    {
        // Enforce AD-003 constraint: all requests to capability-protected routes
        // must come from an authenticated user who holds the exact capability string.

        if (! $request->user() || ! $request->user()->hasCapability($capability)) {
            abort(403, 'Unauthorized access: You do not have the required capability ('.$capability.').');
        }

        return $next($request);
    }
}
