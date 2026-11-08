{{-- Alerta descartable. Tipos: exito, error, aviso, info. --}}
@props(['tipo' => 'info'])
@php
[$clases, $icono] = [
    'exito' => ['bg-green-50 text-green-900 border-green-200', 'check'],
    'error' => ['bg-red-50 text-red-900 border-red-200', 'alerta'],
    'aviso' => ['bg-amber-50 text-amber-900 border-amber-200', 'alerta'],
    'info'  => ['bg-sky-50 text-sky-900 border-sky-200', 'info'],
][$tipo] ?? ['bg-sky-50 text-sky-900 border-sky-200', 'info'];
@endphp
<div x-data="{ visible: true }" x-show="visible" role="{{ $tipo === 'error' ? 'alert' : 'status' }}"
     {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-xl border px-4 py-3 text-sm $clases"]) }}>
    <x-icon :name="$icono" class="w-5 h-5 mt-0.5" />
    <div class="flex-1">{{ $slot }}</div>
    <button type="button" @click="visible = false" class="opacity-60 hover:opacity-100" aria-label="Cerrar aviso"><x-icon name="cerrar" class="w-4 h-4" /></button>
</div>
