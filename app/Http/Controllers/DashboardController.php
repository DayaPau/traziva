<?php

namespace App\Http\Controllers;

use App\Models\Aplicacion;
use App\Models\Cosecha;
use App\Models\LaborCultural;
use App\Models\Plantacion;

/**
 * Panel general. Los totales cuentan SOLO datos reales (es_prueba = false):
 * los datos sintéticos de demostración nunca se mezclan con los de Amanecer.
 */
class DashboardController extends Controller
{
    public function __invoke()
    {
        $reales = fn ($q) => $q->where('es_prueba', false);

        return view('dashboard', [
            'totales' => [
                'Plantaciones activas' => Plantacion::where('estado', 'activa')->where('es_prueba', false)->count(),
                'Labores registradas' => LaborCultural::whereHas('plantacion', $reales)->count(),
                'Aplicaciones' => Aplicacion::whereHas('plantacion', $reales)->count(),
                'Cosechas' => Cosecha::whereHas('plantacion', $reales)->count(),
            ],
            'plantacionesPrueba' => Plantacion::where('es_prueba', true)->count(),
            'plantaciones' => Plantacion::with(['cultivo', 'nave', 'ultimoIdc'])->withCount(['labores', 'aplicaciones', 'cosechas'])
                ->orderBy('es_prueba')->orderBy('codigo')->get(),
        ]);
    }
}
