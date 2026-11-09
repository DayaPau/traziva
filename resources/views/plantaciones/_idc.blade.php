{{-- Insignia del IDC: puntaje con color por categoría, o "Incompleto". Requiere $idc (puede ser null).
     Sin cálculo = sin cosechas: el IDC se recalcula con cada cosecha guardada, así que una plantación
     que nunca se calculó no tiene cosechas y su IDC es incompleto por definición. --}}
@php $color = ['alto' => 'verde', 'medio' => 'amarillo', 'bajo' => 'rojo'][$idc?->categoria] ?? 'gris'; @endphp
@if(! $idc)
    <x-badge color="gris" title="Aún no calculado: faltan cosechas registradas">Incompleto</x-badge>
@elseif($idc->puntaje === null)
    <x-badge color="gris" title="Faltan datos: {{ implode(', ', $idc->datos_faltantes ?? []) }}">Incompleto</x-badge>
@else
    <x-badge :color="$color" title="Categoría {{ $idc->categoria }} · fórmula {{ $idc->version_formula }} (referencial)">{{ number_format($idc->puntaje, 0) }}</x-badge>
@endif
