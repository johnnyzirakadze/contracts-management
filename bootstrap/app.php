<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
        $middleware->alias([
            'role' => App\Http\Middleware\RoleMiddleware::class,
            'jwt.claims' => App\Http\Middleware\VerifyJwtClaimsMiddleware::class,
            'enforce.origin' => App\Http\Middleware\EnforceOriginMiddleware::class,
            'hsts' => App\Http\Middleware\HstsMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (Throwable $e, $request) {
            $isApi = $request->is('api/*') || $request->expectsJson();
            if (! $isApi) {
                return null; // fall back to default HTML handler
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            if ($e instanceof AuthorizationException) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return response()->json(['message' => 'Not Found'], 404);
            }

            if ($e instanceof MethodNotAllowedHttpException) {
                return response()->json(['message' => 'Method Not Allowed'], 405);
            }

            if ($e instanceof ThrottleRequestsException) {
                return response()->json(['message' => 'Too Many Requests'], 429);
            }

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $message = $e->getMessage() ?: ($status >= 500 ? 'Server Error' : 'Error');
                return response()->json(['message' => $message], $status);
            }

            $status = 500;
            $payload = ['message' => 'Server Error'];
            if (config('app.debug')) {
                $payload['exception'] = get_class($e);
                $payload['error'] = $e->getMessage();
            }
            return response()->json($payload, $status);
        });
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Daily database-only backup (incremental-like)
        $schedule->command('backup:run --only-db')->dailyAt('02:00');
        // Weekly full backup on Mondays 03:00
        $schedule->command('backup:run')->weeklyOn(1, '03:00');
    })
    ->create();
