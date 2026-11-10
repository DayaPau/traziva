@extends('layouts.app')
@section('titulo', 'Labores culturales')
@section('contenido')
<x-page-header titulo="Labores culturales" subtitulo="Seguimiento operativo de todas las plantaciones. Para registrar una labor, abre la plantación → pestaña «Labores»." />

<form method="GET" class="card !p-4 mb-6 grid gap-3 md:grid-cols-2 lg:grid-cols-[2fr_1fr_auto_auto_auto]">
    <select name="plantacion_id" class="input-modern" aria-label="Plantación">
        <option value="">Todas las plantaciones</option>
        @foreach($plantaciones as $id => $t)<option value="{{ $id }}" @selected(request('plantacion_id') == $id)>{{ $t }}</option>@endforeach
    </select>
    <select name="tipo_labor_id" class="input-modern" aria-label="Tipo de labor">
        <option value="">Todos los tipos</option>
        @foreach($tipos as $id => $t)<option value="{{ $id }}" @selected(request('tipo_labor_id') == $id)>{{ $t }}</option>@endforeach
    </select>
    <input type="date" name="desde" value="{{ request('desde') }}" class="input-modern" aria-label="Desde">
    <input type="date" name="hasta" value="{{ request('hasta') }}" class="input-modern" aria-label="Hasta">
    <button class="btn-secondary">Filtrar</button>
</form>

@if($labores->isEmpty())
    <x-card><x-empty-state titulo="No hay labores con esos filtros" icono="portapapeles" /></x-card>
@else
<x-table>
    <x-slot:head><th>Fecha</th><th>Plantación</th><th>Labor</th><th>Descripción</th><th>Registró</th><th class="text-right">Acciones</th></x-slot:head>
    @foreach($labores as $l)
        <tr>
            <td class="whitespace-nowrap">{{ $l->fecha->format('d/m/Y') }}</td>
            <td>
                <a href="{{ route('plantaciones.show', $l->plantacion_id) }}#labores" class="font-mono text-xs text-agro-moss hover:underline">{{ $l->plantacion->codigo }}</a>
                @if($l->plantacion->es_prueba)<x-badge color="azul" class="ml-1">Prueba</x-badge>@endif
                <p class="text-xs text-gray-500">{{ $l->plantacion->cultivo->nombre }}{{ $l->plantacion->nave ? ' · Nave '.$l->plantacion->nave->numero : '' }}</p>
            </td>
            <td class="font-medium">{{ $l->tipo->nombre }}</td>
            <td class="text-gray-600">{{ $l->descripcion ?? '—' }}</td>
            <td>{{ $l->registrador?->name ?? 'Importación' }}</td>
            <td class="text-right whitespace-nowrap">
                @can('update', $l)<a href="{{ route('labores.edit', $l) }}" class="text-agro-moss font-medium hover:underline">Editar</a>@endcan
            </td>
        </tr>
    @endforeach
</x-table>
<div class="mt-4">{{ $labores->links() }}</div>
@endif
@endsection
