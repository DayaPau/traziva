<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegistroController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\CosechaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlantacionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UsuarioController;
use App\Models\Plantacion;
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

    // Coordinador/a y responsable: crear y editar plantaciones, catálogos y registros.
    Route::middleware('role:coordinador,responsable')->group(function () {
        Route::get('/plantaciones/crear', [PlantacionController::class, 'create'])->name('plantaciones.create')->can('create', Plantacion::class);
        Route::post('/plantaciones', [PlantacionController::class, 'store'])->name('plantaciones.store')->can('create', Plantacion::class);
        Route::get('/plantaciones/sugerir-codigo', [PlantacionController::class, 'sugerirCodigo'])->name('plantaciones.sugerir-codigo')->can('create', Plantacion::class);
        Route::get('/plantaciones/{plantacion}/editar', [PlantacionController::class, 'edit'])->name('plantaciones.edit')->can('update', 'plantacion');
        Route::put('/plantaciones/{plantacion}', [PlantacionController::class, 'update'])->name('plantaciones.update')->can('update', 'plantacion');
        Route::patch('/plantaciones/{plantacion}/estado', [PlantacionController::class, 'cambiarEstado'])->name('plantaciones.estado')->can('update', 'plantacion');
        Route::delete('/plantaciones/{plantacion}', [PlantacionController::class, 'destroy'])->name('plantaciones.destroy')->can('delete', 'plantacion');

        Route::middleware('can:gestionar-catalogos')->prefix('catalogos/{catalogo}')->name('catalogos.')
            ->whereIn('catalogo', array_keys(\App\Support\Catalogos::todos()))
            ->group(function () {
                Route::get('/', [CatalogoController::class, 'index'])->name('index');
                Route::get('/crear', [CatalogoController::class, 'create'])->name('create');
                Route::post('/', [CatalogoController::class, 'store'])->name('store');
                Route::get('/{id}/editar', [CatalogoController::class, 'edit'])->name('edit')->whereNumber('id');
                Route::put('/{id}', [CatalogoController::class, 'update'])->name('update')->whereNumber('id');
                Route::patch('/{id}/estado', [CatalogoController::class, 'toggle'])->name('toggle')->whereNumber('id');
            });
    });

    Route::get('/plantaciones', [PlantacionController::class, 'index'])->name('plantaciones.index')->can('viewAny', Plantacion::class);
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
        Route::get('/usuarios/crear', [UsuarioController::class, 'create'])->name('usuarios.create')->can('create', User::class);
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store')->can('create', User::class);
        Route::get('/usuarios/{user}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit')->can('update', 'user');
        Route::put('/usuarios/{user}', [UsuarioController::class, 'update'])->name('usuarios.update')->can('update', 'user');
        Route::patch('/usuarios/{user}/estado', [UsuarioController::class, 'toggle'])->name('usuarios.toggle')->can('update', 'user');
        Route::put('/usuarios/{user}/clave', [UsuarioController::class, 'restablecerClave'])->name('usuarios.clave')->can('update', 'user');
    });
});
