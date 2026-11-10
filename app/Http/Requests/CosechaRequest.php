<?php

namespace App\Http\Requests;

use App\Models\Plantacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Registrar o editar una cosecha. La cantidad aprovechable NO se recibe:
 * es una columna generada (cantidad − merma) en la base de datos.
 */
class CosechaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('registrar', $this->plantacion());
    }

    public function rules(): array
    {
        $p = $this->plantacion();
        $actual = $this->route('cosecha')?->categoria_calidad_id;

        return [
            'fecha_cosecha' => array_filter(['required', 'date', 'before_or_equal:today',
                $p->fecha_siembra ? 'after_or_equal:'.$p->fecha_siembra->toDateString() : null]),
            'cantidad_kg' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'merma_kg' => ['nullable', 'numeric', 'min:0', 'lte:cantidad_kg'],
            'categoria_calidad_id' => ['nullable', Rule::exists('categorias_calidad', 'id')->where(fn ($q) => $q->where('activo', true)->orWhere('id', $actual))],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return ['fecha_cosecha' => 'fecha de cosecha', 'cantidad_kg' => 'cantidad (kg)', 'merma_kg' => 'merma (kg)', 'categoria_calidad_id' => 'calidad'];
    }

    public function messages(): array
    {
        return [
            'fecha_cosecha.before_or_equal' => 'La fecha de cosecha no puede estar en el futuro.',
            'fecha_cosecha.after_or_equal' => 'La fecha de cosecha no puede ser anterior a la siembra ('.$this->plantacion()->fecha_siembra?->format('d/m/Y').').',
            'merma_kg.lte' => 'La merma no puede ser mayor que la cantidad cosechada.',
        ];
    }

    /** Datos listos para guardar (merma vacía = 0). */
    public function datos(): array
    {
        return array_merge(
            ['merma_kg' => 0, 'categoria_calidad_id' => null, 'observaciones' => null],
            array_filter($this->validated(), fn ($v) => $v !== null),
        );
    }

    /** Si hay errores, volver a la pestaña "Cosechas" del detalle (y no a "Resumen"). */
    protected function getRedirectUrl(): string
    {
        $p = $this->route('plantacion');

        return $p ? route('plantaciones.show', $p).'#cosechas' : parent::getRedirectUrl();
    }

    private function plantacion(): Plantacion
    {
        return $this->route('plantacion') ?? $this->route('cosecha')->plantacion;
    }
}
