<?php

namespace App\Http\Controllers;

use App\Http\Requests\CosechaRequest;
use App\Models\CategoriaCalidad;
use App\Models\Cosecha;
use App\Models\Plantacion;
use Illuminate\Http\Request;

/**
 * Cosecha y postcosecha. Al guardar o editar una cosecha, el IDC parcial de la plantación
 * se recalcula solo (evento del modelo Cosecha en AppServiceProvider).
 */
class CosechaController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate([
            'plantacion_id' => ['nullable', 'integer'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ]);

        $filtrar = fn ($q) => $q->whereHas('plantacion')
            ->when($f['plantacion_id'] ?? null, fn ($q, $v) => $q->where('plantacion_id', $v))
            ->when($f['desde'] ?? null, fn ($q, $v) => $q->whereDate('fecha_cosecha', '>=', $v))
            ->when($f['hasta'] ?? null, fn ($q, $v) => $q->whereDate('fecha_cosecha', '<=', $v));

        return view('cosechas.index', [
            'cosechas' => $filtrar(Cosecha::with(['plantacion.cultivo', 'plantacion.nave', 'calidad']))
                ->orderByDesc('fecha_cosecha')->orderByDesc('id')->paginate(25)->withQueryString(),
            'totales' => $filtrar(Cosecha::query())
                ->selectRaw('COUNT(*) as cortes, COALESCE(SUM(cantidad_kg),0) as cosechado, COALESCE(SUM(merma_kg),0) as merma, COALESCE(SUM(cantidad_aprovechable_kg),0) as aprovechable')
                ->first(),
            'plantaciones' => Plantacion::with('cultivo')->orderBy('codigo')->get()->mapWithKeys(fn ($p) => [$p->id => "{$p->codigo} · {$p->cultivo->nombre}"]),
        ]);
    }

    public function store(CosechaRequest $request, Plantacion $plantacion)
    {
        $plantacion->cosechas()->create($request->datos() + ['registrado_por' => $request->user()->id]);

        return redirect()->to(route('plantaciones.show', $plantacion).'#cosechas')->with('ok', 'Cosecha registrada. El IDC parcial se recalculó.');
    }

    public function edit(Cosecha $cosecha)
    {
        return view('cosechas.edit', [
            'cosecha' => $cosecha->load('plantacion.cultivo', 'plantacion.nave'),
            'calidades' => CategoriaCalidad::where('activo', true)->orWhere('id', $cosecha->categoria_calidad_id)->orderBy('orden')->pluck('nombre', 'id'),
        ]);
    }

    public function update(CosechaRequest $request, Cosecha $cosecha)
    {
        $cosecha->update($request->datos());

        return redirect()->to(route('plantaciones.show', $cosecha->plantacion_id).'#cosechas')->with('ok', 'Cosecha actualizada. El IDC parcial se recalculó.');
    }
}
