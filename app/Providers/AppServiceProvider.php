<?php

namespace App\Providers;

use App\Models\Cosecha;
use App\Models\User;
use App\Services\Idc\IdcCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(IdcCalculator::class);
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Carbon::setLocale('es');

        // Autorización (las Policies se descubren por convención: App\Policies\{Modelo}Policy).
        // El/la coordinador/a es superadministrador/a.
        Gate::before(fn (User $user) => $user->esCoordinador() ? true : null);
        Gate::define('exportar', fn (User $user) => true);          // D-04: todos los roles exportan CSV
        Gate::define('ver-auditoria', fn (User $user) => false);    // solo coordinador/a (vía before)
        Gate::define('gestionar-catalogos', fn (User $user) => $user->puedeRegistrar());

        // Cada cosecha nueva o modificada recalcula el IDC parcial de su plantación.
        $recalcular = fn (Cosecha $c) => app(IdcCalculator::class)->calcular($c->plantacion, 'parcial', auth()->id());
        Cosecha::saved($recalcular);
        Cosecha::deleted($recalcular);
    }
}
