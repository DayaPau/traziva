<?php

namespace App\Http\Requests;

use App\Models\Plantacion;
use App\Models\TipoLabor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Registrar o editar una labor cultural de una plantación (coordinador/a y responsable). */
class LaborRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('registrar', $this->plantacion());
    }

    public function rules(): array
    {
        $p = $this->plantacion();

        return [
            'tipo_labor_id' => ['required', Rule::in(self::tiposPermitidos($p, $this->route('labor')?->tipo_labor_id)->modelKeys())],
            'fecha' => array_filter(['required', 'date', 'before_or_equal:today',
                $p->fecha_siembra ? 'after_or_equal:'.$p->fecha_siembra->toDateString() : null]),
            'descripcion' => ['nullable', 'string', 'max:300'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return ['tipo_labor_id' => 'tipo de labor', 'fecha' => 'fecha', 'descripcion' => 'descripción'];
    }

    public function messages(): array
    {
        return [
            'tipo_labor_id.in' => 'Elige un tipo de labor activo para este cultivo.',
            'fecha.before_or_equal' => 'La fecha de la labor no puede estar en el futuro.',
            'fecha.after_or_equal' => 'La fecha de la labor no puede ser anterior a la siembra ('.$this->plantacion()->fecha_siembra?->format('d/m/Y').').',
        ];
    }

    /**
     * Tipos de labor que se ofrecen para una plantación: los activos asociados a su cultivo;
     * si el cultivo aún no tiene tipos asociados, todos los activos. Se conserva el actual al editar.
     */
    public static function tiposPermitidos(Plantacion $p, ?int $actual = null)
    {
        $delCultivo = TipoLabor::where('activo', true)->whereHas('cultivos', fn ($q) => $q->whereKey($p->cultivo_id));
        $base = $delCultivo->exists() ? $delCultivo : TipoLabor::where('activo', true);

        return $base->orWhere('id', $actual)->orderBy('nombre')->get();
    }

    /** Si hay errores, volver a la pestaña "Labores" del detalle (y no a "Resumen"). */
    protected function getRedirectUrl(): string
    {
        $p = $this->route('plantacion');

        return $p ? route('plantaciones.show', $p).'#labores' : parent::getRedirectUrl();
    }

    private function plantacion(): Plantacion
    {
        return $this->route('plantacion') ?? $this->route('labor')->plantacion;
    }
}
