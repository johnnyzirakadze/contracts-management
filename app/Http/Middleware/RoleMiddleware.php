<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !$user->role) {
            abort(403, 'Forbidden');
        }

        $userRoleKey = optional($user->role)->key;

        if (empty($roles) || in_array($userRoleKey, $roles, true)) {
            return $next($request);
        }

        abort(403, 'Insufficient role');
    }
}


