<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceOriginMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedOrigins = array_filter(array_map('trim', explode(',', (string) env('ALLOWED_ORIGINS', ''))));

        if (!empty($allowedOrigins)) {
            $origin = (string) $request->headers->get('Origin', '');
            $referer = (string) $request->headers->get('Referer', '');

            $isAllowed = $origin && in_array($origin, $allowedOrigins, true);
            if (!$isAllowed && $referer) {
                foreach ($allowedOrigins as $allowed) {
                    if (str_starts_with($referer, $allowed)) {
                        $isAllowed = true;
                        break;
                    }
                }
            }

            if (!$isAllowed && ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH') || $request->isMethod('DELETE'))) {
                abort(403, 'Origin not allowed');
            }
        }

        return $next($request);
    }
}


