<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegistroController;
use App\Http\Controllers\CosechaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlantacionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UsuarioController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1');
    // Registro con aprobación: la cuenta queda "pendiente" hasta que el/la coordinador/a la apruebe.
    Route::get('/registro', [RegistroController::class, 'create'])->name('registro');
    Route::post('/registro', [RegistroController::class, 'store'])->middleware('throttle:3,10');
});

// Doble control de acceso: middleware de rol (grupos) + Policies/Gates (acción y registro).
Route::middleware(['auth', 'cuenta.vigente'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/plantaciones', [PlantacionController::class, 'index'])->name('plantaciones.index')->can('viewAny', \App\Models\Plantacion::class);
    Route::get('/plantaciones/{plantacion}', [PlantacionController::class, 'show'])->name('plantaciones.show')->can('view', 'plantacion');

    Route::middleware('role:coordinador,responsable')->group(function () {
        Route::post('/plantaciones/{plantacion}/cosechas', [CosechaController::class, 'store'])->name('cosechas.store')->can('registrar', 'plantacion');
        Route::post('/plantaciones/{plantacion}/idc', [PlantacionController::class, 'calcularIdc'])->name('plantaciones.idc')->can('registrar', 'plantacion');
    });

    Route::middleware('can:exportar')->group(function () {
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/exportar/plantaciones.csv', [PlantacionController::class, 'exportar'])->name('plantaciones.exportar');
    });

    Route::middleware('role:coordinador')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index')->can('viewAny', User::class);
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store')->can('create', User::class);
        Route::patch('/usuarios/{user}/estado', [UsuarioController::class, 'toggle'])->name('usuarios.toggle')->can('update', 'user');
    });
});
