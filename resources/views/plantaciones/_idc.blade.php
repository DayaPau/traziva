{{-- Insignia del IDC: puntaje con color por categoría, o "Incompleto". Requiere $idc (puede ser null). --}}
@php $color = ['alto' => 'verde', 'medio' => 'amarillo', 'bajo' => 'rojo'][$idc?->categoria] ?? 'gris'; @endphp
@if(! $idc)
    <span class="text-gray-400">—</span>
@elseif($idc->puntaje === null)
    <x-badge color="gris" title="Faltan datos: {{ implode(', ', $idc->datos_faltantes ?? []) }}">Incompleto</x-badge>
@else
    <x-badge :color="$color" title="Categoría {{ $idc->categoria }} · fórmula {{ $idc->version_formula }}">{{ number_format($idc->puntaje, 0) }}</x-badge>
@endif
