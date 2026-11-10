<?php

namespace App\Http\Controllers;

use App\Http\Requests\LaborRequest;
use App\Models\LaborCultural;
use App\Models\Plantacion;
use App\Models\TipoLabor;
use Illuminate\Http\Request;

/** Labores culturales ("seguimiento operativo"). Crear/editar: coordinador/a y responsable; consultar: todos. */
class LaborController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate([
            'plantacion_id' => ['nullable', 'integer'],
            'tipo_labor_id' => ['nullable', 'integer'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ]);

        $labores = LaborCultural::with(['plantacion.cultivo', 'plantacion.nave', 'tipo', 'registrador'])
            ->whereHas('plantacion') // excluye las de plantaciones retiradas
            ->when($f['plantacion_id'] ?? null, fn ($q, $v) => $q->where('plantacion_id', $v))
            ->when($f['tipo_labor_id'] ?? null, fn ($q, $v) => $q->where('tipo_labor_id', $v))
            ->when($f['desde'] ?? null, fn ($q, $v) => $q->whereDate('fecha', '>=', $v))
            ->when($f['hasta'] ?? null, fn ($q, $v) => $q->whereDate('fecha', '<=', $v))
            ->orderByDesc('fecha')->orderByDesc('id')
            ->paginate(25)->withQueryString();

        return view('labores.index', [
            'labores' => $labores,
            'plantaciones' => Plantacion::with('cultivo')->orderBy('codigo')->get()->mapWithKeys(fn ($p) => [$p->id => "{$p->codigo} · {$p->cultivo->nombre}"]),
            'tipos' => TipoLabor::orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }

    public function store(LaborRequest $request, Plantacion $plantacion)
    {
        $plantacion->labores()->create($request->validated() + ['registrado_por' => $request->user()->id]);

        return redirect()->to(route('plantaciones.show', $plantacion).'#labores')->with('ok', 'Labor cultural registrada.');
    }

    public function edit(LaborCultural $labor)
    {
        return view('labores.edit', [
            'labor' => $labor->load('plantacion.cultivo', 'plantacion.nave'),
            'tipos' => LaborRequest::tiposPermitidos($labor->plantacion, $labor->tipo_labor_id)->pluck('nombre', 'id'),
        ]);
    }

    public function update(LaborRequest $request, LaborCultural $labor)
    {
        $labor->update($request->validated());

        return redirect()->to(route('plantaciones.show', $labor->plantacion_id).'#labores')->with('ok', 'Labor actualizada.');
    }
}
