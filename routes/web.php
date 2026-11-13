<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CosechaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlantacionController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1');
});

Route::middleware(['auth', 'vigente'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/plantaciones', [PlantacionController::class, 'index'])->name('plantaciones.index');
    Route::get('/plantaciones/{plantacion}', [PlantacionController::class, 'show'])->name('plantaciones.show');

    Route::middleware('role:coordinador,responsable,docente')->group(function () {
        Route::post('/plantaciones/{plantacion}/cosechas', [CosechaController::class, 'store'])->name('cosechas.store');
        Route::post('/plantaciones/{plantacion}/idc', [PlantacionController::class, 'calcularIdc'])->name('plantaciones.idc');
    });

    Route::middleware('role:coordinador,responsable')->get('/exportar/plantaciones.csv', [PlantacionController::class, 'exportar'])->name('plantaciones.exportar');

    Route::middleware('role:coordinador')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::patch('/usuarios/{user}/estado', [UsuarioController::class, 'toggle'])->name('usuarios.toggle');
    });
});
