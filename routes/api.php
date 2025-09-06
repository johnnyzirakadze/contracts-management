<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Public auth route(s)
Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])->middleware(['throttle:login','enforce.origin','hsts']);
});

// Everything else under /api requires JWT auth by default
Route::middleware(['auth:api','jwt.claims','enforce.origin','hsts'])->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh'])->middleware(['throttle:refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Example protected routes with RBAC
    Route::get('/admin/users', [AuthController::class, 'listUsers'])->middleware('role:admin');
    Route::post('/admin/users', [AuthController::class, 'createUser'])->middleware('role:admin');
});


