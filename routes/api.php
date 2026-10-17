<?php

use App\Http\Controllers\Api\AuthenticationController;
use App\Http\Controllers\Api\PaqueteApiController;
use App\Http\Controllers\Api\ResidenteApiController;
use App\Http\Middleware\AuthenticateApiToken;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthenticationController::class, 'login'])->middleware('throttle:10,1')->name('api.login');

Route::middleware(AuthenticateApiToken::class)->group(function (): void {
    Route::post('/logout', [AuthenticationController::class, 'logout'])->name('api.logout');

    Route::middleware('role:admin,vigilante,residente')->group(function (): void {
        Route::get('/paquetes', [PaqueteApiController::class, 'index'])->name('api.paquetes.index');
        Route::get('/paquetes/{paquete}', [PaqueteApiController::class, 'show'])->name('api.paquetes.show');
    });

    Route::middleware('role:admin,vigilante')->group(function (): void {
        Route::get('/residentes', [ResidenteApiController::class, 'index'])->name('api.residentes.index');
    });

    Route::middleware('role:vigilante')->group(function (): void {
        Route::post('/paquetes', [PaqueteApiController::class, 'store'])->name('api.paquetes.store');
        Route::post('/paquetes/{paquete}/entrega', [PaqueteApiController::class, 'deliver'])->name('api.paquetes.deliver');
    });
});
