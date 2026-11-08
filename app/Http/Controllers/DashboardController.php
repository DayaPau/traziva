<?php

namespace App\Http\Controllers;

use App\Models\Aplicacion;
use App\Models\Cosecha;
use App\Models\LaborCultural;
use App\Models\Plantacion;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('dashboard', [
            'totales' => [
                'Plantaciones activas' => Plantacion::where('estado', 'activa')->count(),
                'Labores registradas' => LaborCultural::count(),
                'Aplicaciones' => Aplicacion::count(),
                'Cosechas' => Cosecha::count(),
            ],
            'plantaciones' => Plantacion::with(['cultivo', 'nave', 'ultimoIdc'])->withCount(['labores', 'aplicaciones', 'cosechas'])->orderBy('codigo')->get(),
        ]);
    }
}
