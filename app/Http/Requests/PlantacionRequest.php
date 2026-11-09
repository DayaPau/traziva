<?php

namespace App\Http\Requests;

use App\Models\Plantacion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PlantacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $p = $this->route('plantacion');

        return $p ? $this->user()->can('update', $p) : $this->user()->can('create', Plantacion::class);
    }

    public function rules(): array
    {
        $actual = $this->route('plantacion');
        $id = $actual?->id;
        // Obligatorios al crear. Al editar una plantación importada sin nave/fecha, se permite seguir sin ellos
        // (el Excel no los traía o eran dudosos) para no bloquear la corrección de otros campos.
        $obligatorio = fn (string $campo) => $actual && $actual->{$campo} === null ? 'nullable' : 'required';

        return [
            'codigo' => ['nullable', 'string', 'max:40', 'regex:/^[A-Z0-9-]+$/', Rule::unique('plantaciones', 'codigo')->ignore($id)],
            // Al crear solo se aceptan cultivos/naves activos; al editar se conserva el que ya tenía.
            'cultivo_id' => ['required', Rule::exists('cultivos', 'id')->where(fn ($q) => $q->where('activo', true)->orWhere('id', $this->route('plantacion')?->cultivo_id))],
            'nave_id' => [$obligatorio('nave_id'), Rule::exists('naves', 'id')->where(fn ($q) => $q->where('activo', true)->orWhere('id', $this->route('plantacion')?->nave_id))],
            'variedad' => ['nullable', 'string', 'max:80'],
            'num_camas' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'num_plantas' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'fecha_siembra' => [$obligatorio('fecha_siembra'), 'date', 'after_or_equal:2018-01-01', 'before_or_equal:today'],
            'fecha_siembra_precision' => ['required', Rule::in(['dia', 'mes', 'anio'])],
            'fecha_inicio_cosecha_estimada' => ['nullable', 'date', 'after_or_equal:fecha_siembra'],
            'fecha_salida_estimada' => ['nullable', 'date', 'after:fecha_siembra', 'after_or_equal:fecha_inicio_cosecha_estimada'],
            'meta_kg_por_cama' => ['nullable', 'numeric', 'gt:0', 'max:100000', 'required_with:meta_periodo'],
            'meta_periodo' => ['nullable', Rule::in(['mensual', 'anual', 'ciclo_total']), 'required_with:meta_kg_por_cama'],
            'responsable_id' => ['nullable', Rule::in($this->responsablesPermitidos())],
            'estado' => ['required', Rule::in(['activa', 'inactiva', 'finalizada'])],
            'etapa' => ['nullable', Rule::in(['crecimiento', 'floracion', 'cosecha'])],
            'observaciones' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'codigo' => 'código', 'cultivo_id' => 'cultivo', 'nave_id' => 'nave', 'num_camas' => 'número de camas',
            'num_plantas' => 'número de plantas', 'fecha_siembra' => 'fecha de siembra', 'fecha_siembra_precision' => 'precisión de la fecha',
            'fecha_inicio_cosecha_estimada' => 'fecha estimada de inicio de cosecha', 'fecha_salida_estimada' => 'fecha estimada de salida',
            'meta_kg_por_cama' => 'meta (kg por cama)', 'meta_periodo' => 'período de la meta', 'responsable_id' => 'responsable',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.regex' => 'El código solo puede tener letras mayúsculas, números y guiones (ej.: FRU-N06-202509).',
            'fecha_siembra.after_or_equal' => 'La fecha de siembra no puede ser anterior a 2018.',
            'fecha_siembra.before_or_equal' => 'La fecha de siembra no puede estar en el futuro.',
            'fecha_inicio_cosecha_estimada.after_or_equal' => 'El inicio estimado de cosecha no puede ser anterior a la siembra.',
            'fecha_salida_estimada.after' => 'La salida estimada debe ser posterior a la siembra.',
            'fecha_salida_estimada.after_or_equal' => 'La salida estimada no puede ser anterior al inicio de cosecha.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['codigo' => $this->filled('codigo') ? mb_strtoupper(trim($this->input('codigo'))) : null]);
    }

    /** Regla de negocio: no puede haber dos plantaciones con el mismo cultivo + nave + siembra. */
    public function after(): array
    {
        return [function (Validator $v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }
            $d = $this->datos();
            if (empty($d['nave_id']) || empty($d['fecha_siembra'])) {
                return; // sin nave o sin fecha no hay combinación que comparar
            }
            $duplicada = Plantacion::where('cultivo_id', $d['cultivo_id'])
                ->where('nave_id', $d['nave_id'])
                ->whereDate('fecha_siembra', $d['fecha_siembra'])
                ->when($this->route('plantacion'), fn ($q, $p) => $q->whereKeyNot($p->id))
                ->value('codigo');
            if ($duplicada) {
                $v->errors()->add('fecha_siembra', "Ya existe la plantación $duplicada con el mismo cultivo, nave y fecha de siembra.");
            }
        }];
    }

    /** Datos validados con la fecha de siembra normalizada según su precisión (mes → día 1, año → 1 de enero). */
    public function datos(): array
    {
        $d = $this->validated();
        if (empty($d['fecha_siembra'])) {
            return $d;
        }
        $f = Carbon::parse($d['fecha_siembra']);
        $d['fecha_siembra'] = match ($d['fecha_siembra_precision']) {
            'mes' => $f->startOfMonth()->toDateString(),
            'anio' => $f->startOfYear()->toDateString(),
            default => $f->toDateString(),
        };

        return $d;
    }

    /** Responsable de la plantación: coordinador/a o responsable de producción con cuenta activa. */
    private function responsablesPermitidos(): array
    {
        return User::whereHas('rol', fn ($q) => $q->whereIn('nombre', ['coordinador', 'responsable']))
            ->where('estado', 'activo')->pluck('id')
            ->push($this->route('plantacion')?->responsable_id)->filter()->all();
    }
}
