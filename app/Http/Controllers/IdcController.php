<?php

namespace App\Http\Controllers;

use App\Models\Cultivo;
use App\Models\Plantacion;
use App\Services\Idc\IdcCalculator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Sección «Índice de Desempeño»: compara el IDC de todas las plantaciones.
 * Consultar: todos los roles. Recalcular todos: coordinador/a y responsable.
 * El IDC es REFERENCIAL y su fórmula está en borrador (config/traziva.php).
 */
class IdcController extends Controller
{
    public const CATEGORIAS = ['alto' => 'Alto', 'medio' => 'Medio', 'bajo' => 'Bajo', 'incompleto' => 'Incompleto'];

    public function index(Request $request)
    {
        $f = $request->validate([
            'cultivo_id' => ['nullable', 'integer'],
            'categoria' => ['nullable', Rule::in(array_keys(self::CATEGORIAS))],
            'datos' => ['nullable', Rule::in(['reales', 'prueba'])],
        ]);

        $plantaciones = Plantacion::query()
            ->with(['cultivo', 'nave', 'ultimoIdc.variables'])
            ->when($f['cultivo_id'] ?? null, fn ($q, $v) => $q->where('cultivo_id', $v))
            ->when(($f['datos'] ?? null) === 'reales', fn ($q) => $q->where('es_prueba', false))
            ->when(($f['datos'] ?? null) === 'prueba', fn ($q) => $q->where('es_prueba', true))
            ->get()
            // Sin cálculo = sin cosechas = incompleto (el IDC se recalcula con cada cosecha; D-12).
            ->each(fn (Plantacion $p) => $p->setAttribute('categoria_idc', $p->ultimoIdc?->categoria ?? 'incompleto'));

        // Resumen por categoría con el filtro de cultivo/datos, antes de filtrar por categoría.
        $resumen = collect(self::CATEGORIAS)->map(fn ($t, $c) => $plantaciones->where('categoria_idc', $c)->count());

        $lista = $plantaciones
            ->when($f['categoria'] ?? null, fn ($c, $cat) => $c->where('categoria_idc', $cat))
            // Mejor puntaje primero; las incompletas al final.
            ->sortBy([fn ($a, $b) => ($b->ultimoIdc?->puntaje ?? -1) <=> ($a->ultimoIdc?->puntaje ?? -1), ['codigo', 'asc']])
            ->values();

        return view('idc.index', [
            'plantaciones' => $lista,
            'resumen' => $resumen,
            'cultivos' => Cultivo::orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }

    /** Recalcula el IDC parcial de todas las plantaciones activas (útil al cambiar metas o la fórmula). */
    public function recalcular(Request $request, IdcCalculator $calc)
    {
        $activas = Plantacion::where('estado', 'activa')->get();
        foreach ($activas as $p) {
            $calc->calcular($p, 'parcial', $request->user()->id);
        }

        return back()->with('ok', "IDC recalculado para {$activas->count()} plantaciones activas (referencial, fórmula en borrador).");
    }
}
