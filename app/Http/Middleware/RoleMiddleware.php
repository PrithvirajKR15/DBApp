<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('auth-login-basic')->with('error', 'Please log in to access this page.');
        }

        $user = Auth::user();
        if ($user->hasRole(...$roles)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Unauthorized action. You do not have the required role.'], 403);
        }

        abort(403, 'Unauthorized action. You do not have the required role.');
    }
}
