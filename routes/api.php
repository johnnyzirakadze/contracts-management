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

    // Contracts
    Route::get('/contracts', [ContractsController::class, 'index'])->middleware('role:viewer,editor,approver,admin');
    Route::get('/contracts/export', [ContractsController::class, 'export'])->middleware('role:viewer,editor,approver,admin');
    Route::get('/contracts/{id}/attachments', [ContractsController::class, 'listAttachments'])->middleware('role:viewer,editor,approver,admin');
    Route::post('/contracts', [ContractsController::class, 'store'])->middleware('role:editor,admin');
    Route::put('/contracts/{id}', [ContractsController::class, 'update'])->middleware('role:editor,approver,admin');
    Route::post('/contracts/{id}/attachments', [ContractsController::class, 'uploadAttachment'])->middleware('role:editor,admin');
    Route::delete('/contracts/{id}', [ContractsController::class, 'destroy'])->middleware('role:admin');

    // Contractors directory (reference data)
    Route::get('/contractors', [ContractorsController::class, 'index'])->middleware('role:viewer,editor,approver,admin');
    Route::post('/contractors', [ContractorsController::class, 'store'])->middleware('role:admin');
    Route::put('/contractors/{id}', [ContractorsController::class, 'update'])->middleware('role:admin');
    Route::delete('/contractors/{id}', [ContractorsController::class, 'destroy'])->middleware('role:admin');

    // Audit logs (visible only to Approver/Admin)
    Route::get('/audit-logs', [AuditLogsController::class, 'index'])->middleware('role:approver,admin');
    Route::get('/audit-logs/export', [AuditLogsController::class, 'export'])->middleware('role:approver,admin');
});


