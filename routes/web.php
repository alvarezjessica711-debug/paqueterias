<?php

use App\Http\Controllers\ApartamentoController;
use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\AutorizacionRetiroController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\GuardaController;
use App\Http\Controllers\PaqueteController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ResidenteController;
use App\Http\Controllers\ResidentePaqueteController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']))->name('health');
Route::view('/demo-react', 'demo-react')->name('demo-react');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::middleware('role:admin,vigilante,residente')->group(function () {
        Route::get('/dashboard', [PaqueteController::class, 'dashboard'])->name('dashboard');
    });

    Route::middleware('role:vigilante')->group(function () {
        Route::get('/', [PaqueteController::class, 'create'])->name('registro-paquetes');
        Route::get('/paquetes', [PaqueteController::class, 'index'])->name('paquetes');
        Route::post('/paquetes', [PaqueteController::class, 'store'])->name('paquetes.store');
        Route::get('/entrega/{paquete?}', [PaqueteController::class, 'entrega'])->name('entrega');
        Route::post('/entrega/{paquete}', [PaqueteController::class, 'confirmarEntrega'])->name('entrega.confirmar');
        Route::get('/consultar', [PaqueteController::class, 'consultar'])->name('consultar');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/residentes', [ResidenteController::class, 'index'])->name('residentes');
        Route::post('/residentes', [ResidenteController::class, 'store'])->name('residentes.store');
        Route::get('/residentes/{residente}/editar', [ResidenteController::class, 'edit'])->name('residentes.edit');
        Route::patch('/residentes/{residente}', [ResidenteController::class, 'update'])->name('residentes.update');
        Route::patch('/residentes/{residente}/estado', [ResidenteController::class, 'toggle'])->name('residentes.toggle');
        Route::get('/residentes/{residente}/cuenta/crear', [ResidenteController::class, 'createAccount'])->name('residentes.cuenta.create');
        Route::post('/residentes/{residente}/cuenta', [ResidenteController::class, 'storeAccount'])->name('residentes.cuenta.store');

        Route::get('/guardas', [GuardaController::class, 'index'])->name('guardas');
        Route::post('/guardas', [GuardaController::class, 'store'])->name('guardas.store');
        Route::get('/guardas/{user}/editar', [GuardaController::class, 'edit'])->name('guardas.edit');
        Route::patch('/guardas/{user}', [GuardaController::class, 'update'])->name('guardas.update');
        Route::patch('/guardas/{user}/estado', [GuardaController::class, 'toggle'])->name('guardas.toggle');

        Route::get('/apartamentos', [ApartamentoController::class, 'index'])->name('apartamentos');
        Route::post('/apartamentos', [ApartamentoController::class, 'store'])->name('apartamentos.store');
        Route::patch('/apartamentos/{apartamento}', [ApartamentoController::class, 'update'])->name('apartamentos.update');
        Route::patch('/apartamentos/{apartamento}/estado', [ApartamentoController::class, 'toggle'])->name('apartamentos.toggle');

        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes');
        Route::get('/reportes/exportar', [ReporteController::class, 'exportar'])->name('reportes.exportar');

        Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion');
        Route::post('/configuracion/perfil', [ConfiguracionController::class, 'actualizarPerfil'])->name('configuracion.perfil');
        Route::post('/configuracion/mensaje', [ConfiguracionController::class, 'actualizarMensaje'])->name('configuracion.mensaje');
        Route::post('/configuracion/password', [ConfiguracionController::class, 'actualizarPassword'])->name('configuracion.password');
    });

    Route::middleware('role:admin,vigilante,residente')->group(function () {
        Route::get('/historial', [PaqueteController::class, 'historial'])->name('historial');
    });

    Route::middleware('role:residente')->group(function () {
        Route::get('/dashboard-residente', [ResidentePaqueteController::class, 'dashboard'])->name('dashboard-residente');
        Route::get('/mis-paquetes', [ResidentePaqueteController::class, 'index'])->name('mis-paquetes');
        Route::get('/mis-paquetes/{paquete}/autorizacion', [AutorizacionRetiroController::class, 'create'])->name('autorizaciones.create');
        Route::post('/mis-paquetes/{paquete}/autorizacion', [AutorizacionRetiroController::class, 'store'])->name('autorizaciones.store');
        Route::patch('/mis-paquetes/{paquete}/autorizacion/{autorizacion}/cancelar', [AutorizacionRetiroController::class, 'cancelar'])->name('autorizaciones.cancelar');
    });

    Route::get('/confirmacion', function () {
        return view('confirmacion');
    })->name('confirmacion');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
