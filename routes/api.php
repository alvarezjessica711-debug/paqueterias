<?php

use App\Http\Controllers\Api\AuthenticationController;
use App\Http\Controllers\Api\PaqueteApiController;
use App\Http\Controllers\Api\ResidenteApiController;
use App\Http\Controllers\Api\UserManagementApiController;
use App\Http\Middleware\AuthenticateApiToken;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthenticationController::class, 'login'])->middleware('throttle:10,1')->name('api.login');

Route::middleware(AuthenticateApiToken::class)->group(function (): void {
    Route::post('/logout', [AuthenticationController::class, 'logout'])->name('api.logout');

    Route::middleware('role:admin,vigilante,residente,superadmin')->group(function (): void {
        Route::get('/paquetes', [PaqueteApiController::class, 'index'])->name('api.paquetes.index');
        Route::get('/paquetes/{paquete}', [PaqueteApiController::class, 'show'])->name('api.paquetes.show');
    });

    Route::middleware('role:admin,vigilante,superadmin')->group(function (): void {
        Route::get('/residentes', [ResidenteApiController::class, 'index'])->name('api.residentes.index');
        Route::get('/paquetes/{paquete}/historial', [PaqueteApiController::class, 'history'])->name('api.paquetes.history');
    });

    Route::middleware('role:superadmin')->group(function (): void {
        Route::post('/users', [UserManagementApiController::class, 'store'])->name('api.users.store');
    });

    Route::middleware('role:vigilante')->group(function (): void {
        Route::post('/paquetes', [PaqueteApiController::class, 'store'])->name('api.paquetes.store');
    });

    Route::middleware('role:vigilante')->group(function (): void {
        Route::put('/paquetes/{paquete}', [PaqueteApiController::class, 'update'])->name('api.paquetes.update');
        Route::patch('/paquetes/{paquete}', [PaqueteApiController::class, 'update']);
    });

    Route::middleware('role:vigilante')->group(function (): void {
        Route::post('/paquetes/{paquete}/entrega/validar', [PaqueteApiController::class, 'validateDeliveryCode'])->name('api.paquetes.delivery.validate');
        Route::post('/paquetes/{paquete}/entrega', [PaqueteApiController::class, 'deliver'])->name('api.paquetes.deliver');
    });
});
