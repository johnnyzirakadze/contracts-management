<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class VerifyJwtClaimsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $token = JWTAuth::getToken();
            if (! $token) {
                abort(401, 'Unauthorized');
            }

            $payload = JWTAuth::getPayload($token);
        } catch (\Throwable $e) {
            abort(401, 'Invalid token');
        }

        $user = auth('api')->user();
        if (! $user) {
            abort(401, 'Unauthorized');
        }

        // token_version binding
        $tokenVersion = (int) ($payload->get('tv') ?? 0);
        if ($tokenVersion !== (int) ($user->token_version ?? 1)) {
            abort(401, 'Token revoked');
        }

        // device binding (optional, present only if set)
        $claimDevice = (string) ($payload->get('dev') ?? '');
        $cookieDevice = (string) ($request->cookies->get('device_id', ''));
        if ($claimDevice !== '' && $cookieDevice !== '' && $claimDevice !== $cookieDevice) {
            abort(401, 'Invalid device');
        }

        return $next($request);
    }
}


