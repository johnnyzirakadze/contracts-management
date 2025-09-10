<?php

use App\Http\Controllers\ContractsController;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContractorsController;
use App\Http\Controllers\AuditLogsController;

// No public routes except login

// Public auth route(s)
Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])->middleware(['throttle:login','enforce.origin','hsts']);
    Route::post('refresh', [AuthController::class, 'refreshWithCookie'])->middleware(['throttle:refresh','enforce.origin','hsts']);
});

// Everything else under /api requires JWT auth by default
Route::middleware(['auth:api','jwt.claims','enforce.origin','hsts'])->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Example protected routes with RBAC
    Route::get('/admin/users', [AuthController::class, 'listUsers'])->middleware('role:admin');
    Route::post('/admin/users', [AuthController::class, 'createUser'])->middleware('role:admin');

    // Contracts (JWT required)
    Route::get('/contracts', [ContractsController::class, 'index']);
    Route::get('/contracts/export', [ContractsController::class, 'export']);
    Route::get('/contracts/{id}/attachments', [ContractsController::class, 'listAttachments']);
    Route::post('/contracts', [ContractsController::class, 'store']);
    Route::put('/contracts/{id}', [ContractsController::class, 'update']);
    Route::post('/contracts/{id}/attachments', [ContractsController::class, 'uploadAttachment']);
    Route::delete('/contracts/{id}', [ContractsController::class, 'destroy']);

    // Contractors directory (JWT required)
    Route::get('/contractors', [ContractorsController::class, 'index']);
    Route::post('/contractors', [ContractorsController::class, 'store']);
    Route::put('/contractors/{id}', [ContractorsController::class, 'update']);
    Route::delete('/contractors/{id}', [ContractorsController::class, 'destroy']);

    // Audit logs
    Route::get('/audit-logs', [AuditLogsController::class, 'index']);
    Route::get('/audit-logs/export', [AuditLogsController::class, 'export']);
});


