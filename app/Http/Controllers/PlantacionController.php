<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlantacionRequest;
use App\Models\Auditoria;
use App\Models\CategoriaCalidad;
use App\Models\Cultivo;
use App\Models\Nave;
use App\Models\Plantacion;
use App\Models\User;
use App\Services\Idc\IdcCalculator;
use App\Services\Plantaciones\CodigoPlantacion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** "Gestión de cultivos" en la interfaz: cada fila es una plantación (cultivo + nave + siembra). */
class PlantacionController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'estado' => ['nullable', Rule::in(['activa', 'inactiva', 'finalizada'])],
            'cultivo_id' => ['nullable', 'integer'],
            'datos' => ['nullable', Rule::in(['reales', 'prueba'])],
        ]);

        $plantaciones = Plantacion::query()
            ->with(['cultivo', 'nave', 'responsable', 'ultimoIdc'])
            ->when($filtros['q'] ?? null, function ($q, $texto) {
                $q->where(fn ($w) => $w->where('codigo', 'like', "%$texto%")
                    ->orWhere('variedad', 'like', "%$texto%")
                    ->orWhereHas('cultivo', fn ($c) => $c->where('nombre', 'like', "%$texto%"))
                    ->orWhereHas('nave', fn ($n) => $n->where('numero', 'like', '%'.preg_replace('/^nave\s*/i', '', $texto).'%')));
            })
            ->when($filtros['estado'] ?? null, fn ($q, $e) => $q->where('estado', $e))
            ->when($filtros['cultivo_id'] ?? null, fn ($q, $c) => $q->where('cultivo_id', $c))
            ->when(($filtros['datos'] ?? null) === 'reales', fn ($q) => $q->where('es_prueba', false))
            ->when(($filtros['datos'] ?? null) === 'prueba', fn ($q) => $q->where('es_prueba', true))
            ->orderBy('codigo')
            ->paginate(20)
            ->withQueryString();

        return view('plantaciones.index', [
            'plantaciones' => $plantaciones,
            'cultivos' => Cultivo::orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }

    public function create(Request $request)
    {
        $p = new Plantacion(['estado' => 'activa', 'fecha_siembra_precision' => 'dia', 'responsable_id' => $request->user()->id]);

        return view('plantaciones.form', ['p' => $p] + $this->opciones($p));
    }

    public function store(PlantacionRequest $request)
    {
        $d = $request->datos();
        $d['codigo'] ??= $this->codigoPara($d);

        $p = Plantacion::create($d + ['created_by' => $request->user()->id, 'es_prueba' => false]);

        return redirect()->route('plantaciones.show', $p)->with('ok', "Plantación {$p->codigo} registrada.");
    }

    public function show(Plantacion $plantacion)
    {
        $plantacion->load(['cultivo', 'nave', 'responsable', 'creador', 'ultimoIdc.variables']);

        $labores = $plantacion->labores()->with(['tipo', 'registrador'])->orderByDesc('fecha')->get();
        $aplicaciones = $plantacion->aplicaciones()->with(['detalles.insumo', 'plaga'])->orderByDesc('fecha_aplicacion')->get();
        $eventos = $plantacion->eventos()->with('plaga')->orderByDesc('fecha_evento')->get();
        $cosechas = $plantacion->cosechas()->with('calidad')->orderByDesc('fecha_cosecha')->get();

        // Historial: cambios auditados de la plantación y de todos sus registros.
        $entidades = [
            'plantaciones' => [$plantacion->id],
            'labores_culturales' => $labores->modelKeys(),
            'aplicaciones' => $aplicaciones->modelKeys(),
            'eventos_fitosanitarios' => $eventos->modelKeys(),
            'cosechas' => $cosechas->modelKeys(),
        ];
        $historial = Auditoria::with('user')
            ->where(function ($q) use ($entidades) {
                foreach (array_filter($entidades) as $entidad => $ids) {
                    $q->orWhere(fn ($w) => $w->where('entidad', $entidad)->whereIn('entidad_id', $ids));
                }
            })
            ->orderByDesc('created_at')->orderByDesc('id')->limit(100)->get();

        return view('plantaciones.show', [
            'p' => $plantacion,
            'labores' => $labores,
            'aplicaciones' => $aplicaciones,
            'eventos' => $eventos,
            'cosechas' => $cosechas,
            'historial' => $historial,
            'calculosIdc' => $plantacion->idcCalculos()->orderByDesc('calculado_en')->limit(20)->get(),
            'calidades' => CategoriaCalidad::where('activo', true)->orderBy('orden')->pluck('nombre', 'id'),
        ]);
    }

    public function edit(Plantacion $plantacion)
    {
        return view('plantaciones.form', ['p' => $plantacion] + $this->opciones($plantacion));
    }

    public function update(PlantacionRequest $request, Plantacion $plantacion)
    {
        $d = $request->datos();
        $d['codigo'] ??= $plantacion->codigo;
        $plantacion->update($d);

        return redirect()->route('plantaciones.show', $plantacion)->with('ok', 'Cambios guardados.');
    }

    /** Cambio rápido de estado (y etapa, si se envía) desde el listado. Queda auditado por el modelo. */
    public function cambiarEstado(Request $request, Plantacion $plantacion)
    {
        $d = $request->validate([
            'estado' => ['required', Rule::in(array_keys(Plantacion::ESTADOS))],
            'etapa' => ['sometimes', 'nullable', Rule::in(array_keys(Plantacion::ETAPAS))],
        ]);
        $plantacion->update($d);

        $etapa = $plantacion->etapa ? ', etapa «'.Plantacion::ETAPAS[$plantacion->etapa].'»' : '';

        return back()->with('ok', "{$plantacion->codigo}: estado «".Plantacion::ESTADOS[$plantacion->estado]."»{$etapa}.");
    }

    /** Solo se retira (soft delete) una plantación SIN registros; si tiene historial se cambia su estado. */
    public function destroy(Plantacion $plantacion)
    {
        if ($plantacion->tieneRegistros()) {
            return back()->withErrors(['plantacion' => 'No se puede eliminar una plantación con registros. Cambia su estado a «inactiva» o «finalizada».']);
        }
        $plantacion->delete();

        return redirect()->route('plantaciones.index')->with('ok', "Plantación {$plantacion->codigo} eliminada.");
    }

    /** Sugerencia del código para el formulario (se llama por fetch al cambiar cultivo, nave o fecha). */
    public function sugerirCodigo(Request $request)
    {
        $d = $request->validate([
            'cultivo_id' => ['required', 'exists:cultivos,id'],
            'nave_id' => ['nullable', 'exists:naves,id'],
            'fecha_siembra' => ['nullable', 'date'],
            'fecha_siembra_precision' => ['nullable', Rule::in(['dia', 'mes', 'anio'])],
        ]);

        return response()->json(['codigo' => $this->codigoPara($d + ['fecha_siembra_precision' => 'dia'])]);
    }

    public function calcularIdc(Plantacion $plantacion, IdcCalculator $calc, Request $request)
    {
        $calc->calcular($plantacion, 'parcial', $request->user()->id);

        return redirect()->to(route('plantaciones.show', $plantacion).'#idc')->with('ok', 'IDC recalculado (referencial, fórmula en borrador).');
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
        }, 'plantaciones-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function codigoPara(array $d): string
    {
        return CodigoPlantacion::generar(
            Cultivo::findOrFail($d['cultivo_id']),
            isset($d['nave_id']) ? Nave::find($d['nave_id'])?->numero : null,
            ! empty($d['fecha_siembra']) ? Carbon::parse($d['fecha_siembra']) : null,
            $d['fecha_siembra_precision'] ?? 'dia',
        );
    }

    /** Opciones de los selects: solo catálogos activos, más el valor actual si fue desactivado. */
    private function opciones(Plantacion $p): array
    {
        return [
            'cultivos' => Cultivo::where('activo', true)->orWhere('id', $p->cultivo_id)->orderBy('nombre')->pluck('nombre', 'id'),
            'naves' => Nave::where('activo', true)->orWhere('id', $p->nave_id)->get()
                ->sortBy(fn ($n) => [(int) $n->numero, $n->numero])->mapWithKeys(fn ($n) => [$n->id => 'Nave '.$n->numero]),
            'responsables' => User::whereHas('rol', fn ($q) => $q->whereIn('nombre', ['coordinador', 'responsable']))
                ->where(fn ($q) => $q->where('estado', 'activo')->orWhere('id', $p->responsable_id))
                ->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
