{{-- Campos de una cosecha con vista previa del aprovechable (cantidad − merma).
     Requiere $calidades [id => nombre], $p (plantación) y opcional $cosecha. --}}
@php $cosecha ??= null; @endphp
<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4"
     x-data="{ cantidad: @js((float) old('cantidad_kg', $cosecha?->cantidad_kg ?? 0)), merma: @js((float) old('merma_kg', $cosecha?->merma_kg ?? 0)) }">
    <x-input name="fecha_cosecha" label="Fecha *" type="date" :value="$cosecha?->fecha_cosecha?->format('Y-m-d')" required
             max="{{ today()->toDateString() }}" min="{{ $p->fecha_siembra?->toDateString() }}" />
    <x-input name="cantidad_kg" label="Cantidad (kg) *" type="number" step="0.001" min="0.001" :value="$cosecha?->cantidad_kg" required
             x-model.number="cantidad" />
    <x-input name="merma_kg" label="Merma (kg)" type="number" step="0.001" min="0" :value="$cosecha?->merma_kg" x-model.number="merma"
             ayuda="0 si no hubo pérdidas." />
    <x-select name="categoria_calidad_id" label="Calidad (provisional)" :opciones="$calidades" :valor="$cosecha?->categoria_calidad_id" vacio="Selecciona…" />
    <div class="md:col-span-2 lg:col-span-3">
        <label for="observaciones" class="label">Observaciones</label>
        <input id="observaciones" name="observaciones" value="{{ old('observaciones', $cosecha?->observaciones) }}" maxlength="1000" class="input-modern">
    </div>
    <div class="rounded-xl bg-agro-cream px-4 py-2.5 self-end">
        <p class="text-xs text-gray-500">Aprovechable (calculado)</p>
        <p class="font-semibold tabular-nums" :class="merma > cantidad ? 'text-red-600' : 'text-agro-forest'"
           x-text="merma > cantidad ? 'Merma mayor que la cantidad' : (Math.max(0, (cantidad || 0) - (merma || 0))).toLocaleString('es-EC', { maximumFractionDigits: 3 }) + ' kg'"></p>
    </div>
</div>
