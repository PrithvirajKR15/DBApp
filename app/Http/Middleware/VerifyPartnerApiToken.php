<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared secret for partner / ordering-app webhooks (order + store sync).
 * Expect header: Authorization: Bearer <PARTNER_API_TOKEN>
 *            or: X-Partner-Token: <PARTNER_API_TOKEN>
 */
class VerifyPartnerApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.partner.api_token');

        if ($expected === '') {
            return response()->json([
                'message' => 'Partner API token is not configured on the server.',
            ], 503);
        }

        $provided = $request->bearerToken()
            ?: $request->header('X-Partner-Token');

        if (! is_string($provided) || ! hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
