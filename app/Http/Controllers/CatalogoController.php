<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogoRequest;
use App\Support\Catalogos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Administración de catálogos (cultivos, naves, tipos de labor, insumos,
 * plagas/enfermedades y categorías de calidad). Ver App\Support\Catalogos.
 * No se borra nada: se desactiva, para conservar el historial de los registros.
 */
class CatalogoController extends Controller
{
    public function index(Request $request, string $catalogo)
    {
        $def = Catalogos::get($catalogo);
        $relaciones = collect($def['campos'])->filter(fn ($c) => $c['tipo'] === 'relacion')->keys()->all();
        $campoNombre = array_key_first($def['campos']);

        $registros = $def['modelo']::query()
            ->with($relaciones)
            ->withCount($def['conteo'])
            ->when($request->filled('q'), fn ($q) => $q->where($campoNombre, 'like', '%'.$request->string('q').'%'))
            ->when($request->input('estado') === 'activos', fn ($q) => $q->where('activo', true))
            ->when($request->input('estado') === 'inactivos', fn ($q) => $q->where('activo', false))
            ->orderByDesc('activo')
            ->orderBy($catalogo === 'calidades' ? 'orden' : $campoNombre)
            ->get();

        return view('catalogos.index', compact('catalogo', 'def', 'registros', 'campoNombre'));
    }

    public function create(string $catalogo)
    {
        $def = Catalogos::get($catalogo);

        return view('catalogos.form', ['catalogo' => $catalogo, 'def' => $def, 'registro' => new $def['modelo']]);
    }

    public function store(CatalogoRequest $request, string $catalogo)
    {
        $def = Catalogos::get($catalogo);
        $registro = $this->guardar(new $def['modelo'], $def, $request->validated());

        return redirect()->route('catalogos.index', $catalogo)
            ->with('ok', ucfirst($def['singular'])." «{$registro->{array_key_first($def['campos'])}}» creado.");
    }

    public function edit(string $catalogo, int $id)
    {
        $def = Catalogos::get($catalogo);

        return view('catalogos.form', ['catalogo' => $catalogo, 'def' => $def, 'registro' => $def['modelo']::findOrFail($id)]);
    }

    public function update(CatalogoRequest $request, string $catalogo, int $id)
    {
        $def = Catalogos::get($catalogo);
        $this->guardar($def['modelo']::findOrFail($id), $def, $request->validated());

        return redirect()->route('catalogos.index', $catalogo)->with('ok', 'Cambios guardados.');
    }

    /** Activar / desactivar. Un registro inactivo deja de ofrecerse en los formularios, pero sigue en el historial. */
    public function toggle(string $catalogo, int $id)
    {
        $def = Catalogos::get($catalogo);
        $registro = $def['modelo']::findOrFail($id);
        $registro->update(['activo' => ! $registro->activo]);

        return back()->with('ok', $registro->activo ? 'Registro activado.' : 'Registro desactivado. Ya no aparecerá en los formularios nuevos.');
    }

    private function guardar(Model $registro, array $def, array $datos): Model
    {
        $relaciones = collect($def['campos'])->filter(fn ($c) => $c['tipo'] === 'relacion')->keys();

        return DB::transaction(function () use ($registro, $datos, $relaciones) {
            $registro->fill(collect($datos)->except($relaciones)->all())->save();
            foreach ($relaciones as $rel) {
                $registro->{$rel}()->sync($datos[$rel] ?? []);
            }

            return $registro;
        });
    }
}
