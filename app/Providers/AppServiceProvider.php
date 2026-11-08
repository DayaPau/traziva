<?php

namespace App\Providers;

use App\Models\Cosecha;
use App\Services\Idc\IdcCalculator;
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

        // Cada cosecha nueva o modificada recalcula el IDC parcial de su plantación.
        $recalcular = fn (Cosecha $c) => app(IdcCalculator::class)->calcular($c->plantacion, 'parcial', auth()->id());
        Cosecha::saved($recalcular);
        Cosecha::deleted($recalcular);
    }
}
