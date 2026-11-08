<?php

namespace App\Http\Controllers;

use App\Models\CategoriaCalidad;
use App\Models\Plantacion;
use App\Services\Idc\IdcCalculator;
use Illuminate\Http\Request;

class PlantacionController extends Controller
{
    public function index(Request $request)
    {
        $q = Plantacion::with(['cultivo', 'nave', 'ultimoIdc'])->withCount(['labores', 'aplicaciones', 'cosechas']);
        if ($request->filled('estado')) {
            $q->where('estado', $request->string('estado'));
        }
        if ($request->boolean('solo_reales')) {
            $q->where('es_prueba', false);
        }

        return view('plantaciones.index', ['plantaciones' => $q->orderBy('codigo')->get()]);
    }

    public function show(Plantacion $plantacion)
    {
        $plantacion->load(['cultivo', 'nave', 'ultimoIdc.variables']);

        return view('plantaciones.show', [
            'p' => $plantacion,
            'labores' => $plantacion->labores()->with('tipo')->orderByDesc('fecha')->get(),
            'aplicaciones' => $plantacion->aplicaciones()->with(['detalles.insumo', 'plaga'])->orderByDesc('fecha_aplicacion')->get(),
            'cosechas' => $plantacion->cosechas()->with('calidad')->orderByDesc('fecha_cosecha')->get(),
            'calidades' => CategoriaCalidad::orderBy('orden')->get(),
        ]);
    }

    public function calcularIdc(Plantacion $plantacion, IdcCalculator $calc, Request $request)
    {
        $calc->calcular($plantacion, 'parcial', $request->user()->id);

        return back()->with('ok', 'IDC recalculado (referencial, fórmula en borrador).');
    }

    public function exportar()
    {
        $filas = Plantacion::with(['cultivo', 'nave'])->withCount(['labores', 'aplicaciones', 'cosechas'])->orderBy('codigo')->get();

        return response()->streamDownload(function () use ($filas) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['codigo', 'cultivo', 'nave', 'siembra', 'camas', 'plantas', 'estado', 'labores', 'aplicaciones', 'cosechas'], ';');
            foreach ($filas as $p) {
                fputcsv($f, [$p->codigo, $p->cultivo->nombre, $p->nave?->numero, $p->siembra_texto, $p->num_camas, $p->num_plantas, $p->estado, $p->labores_count, $p->aplicaciones_count, $p->cosechas_count], ';');
            }
            fclose($f);
        }, 'plantaciones-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
