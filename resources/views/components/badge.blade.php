{{-- Insignia redondeada. Colores: verde, gris, amarillo, rojo, tierra, azul. --}}
@props(['color' => 'gris'])
@php
$clases = [
    'verde'    => 'bg-green-50 text-green-800 ring-green-200',
    'gris'     => 'bg-gray-100 text-gray-700 ring-gray-200',
    'amarillo' => 'bg-amber-50 text-amber-800 ring-amber-200',
    'rojo'     => 'bg-red-50 text-red-700 ring-red-200',
    'tierra'   => 'bg-orange-50 text-orange-800 ring-orange-200',
    'azul'     => 'bg-sky-50 text-sky-800 ring-sky-200',
][$color] ?? 'bg-gray-100 text-gray-700 ring-gray-200';
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset whitespace-nowrap $clases"]) }}>{{ $slot }}</span>
