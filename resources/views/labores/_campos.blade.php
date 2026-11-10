{{-- Campos de una labor cultural. Requiere $tipos [id => nombre], $p (plantación) y opcional $labor. --}}
@php $labor ??= null; @endphp
<x-select name="tipo_labor_id" label="Tipo de labor *" :opciones="$tipos" :valor="$labor?->tipo_labor_id" vacio="Selecciona…" required />
<x-input name="fecha" label="Fecha *" type="date" :value="$labor?->fecha?->format('Y-m-d')" required
         max="{{ today()->toDateString() }}" min="{{ $p->fecha_siembra?->toDateString() }}" />
<x-input name="descripcion" label="Descripción" :value="$labor?->descripcion" maxlength="300" placeholder="Ej.: deshierbe manual en camas 1 a 4" />
