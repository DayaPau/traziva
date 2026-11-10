@extends('layouts.app')
@section('titulo', 'Cosecha y postcosecha')
@php $kg = fn ($n) => number_format((float) $n, 2, ',', '.').' kg'; @endphp
@section('contenido')
<x-page-header titulo="Cosecha y postcosecha" subtitulo="Cosechas de todas las plantaciones. Para registrar una, abre la plantación → pestaña «Cosechas»." />

<form method="GET" class="card !p-4 mb-6 grid gap-3 md:grid-cols-[2fr_auto_auto_auto]">
    <select name="plantacion_id" class="input-modern" aria-label="Plantación">
        <option value="">Todas las plantaciones</option>
        @foreach($plantaciones as $id => $t)<option value="{{ $id }}" @selected(request('plantacion_id') == $id)>{{ $t }}</option>@endforeach
    </select>
    <input type="date" name="desde" value="{{ request('desde') }}" class="input-modern" aria-label="Desde">
    <input type="date" name="hasta" value="{{ request('hasta') }}" class="input-modern" aria-label="Hasta">
    <button class="btn-secondary">Filtrar</button>
</form>

<div class="grid gap-4 grid-cols-2 lg:grid-cols-4 mb-6">
    <div class="card !p-4"><p class="text-sm text-gray-500">Cortes</p><p class="text-2xl font-semibold tabular-nums">{{ $totales->cortes }}</p></div>
    <div class="card !p-4"><p class="text-sm text-gray-500">Cosechado</p><p class="text-2xl font-semibold tabular-nums">{{ $kg($totales->cosechado) }}</p></div>
    <div class="card !p-4"><p class="text-sm text-gray-500">Merma</p><p class="text-2xl font-semibold tabular-nums">{{ $kg($totales->merma) }}</p></div>
    <div class="card !p-4"><p class="text-sm text-gray-500">Aprovechable</p><p class="text-2xl font-semibold tabular-nums text-agro-forest">{{ $kg($totales->aprovechable) }}</p></div>
</div>

@if($cosechas->isEmpty())
    <x-card><x-empty-state titulo="Sin cosechas registradas" icono="cosecha">Registra la primera desde el detalle de una plantación.</x-empty-state></x-card>
@else
<x-table>
    <x-slot:head><th>Fecha</th><th>Plantación</th><th class="text-right">Cantidad</th><th class="text-right">Merma</th><th class="text-right">Aprovechable</th><th>Calidad</th><th class="text-right">Acciones</th></x-slot:head>
    @foreach($cosechas as $c)
        <tr>
            <td class="whitespace-nowrap">{{ $c->fecha_cosecha->format('d/m/Y') }}</td>
            <td>
                <a href="{{ route('plantaciones.show', $c->plantacion_id) }}#cosechas" class="font-mono text-xs text-agro-moss hover:underline">{{ $c->plantacion->codigo }}</a>
                @if($c->plantacion->es_prueba)<x-badge color="azul" class="ml-1">Prueba</x-badge>@endif
                <p class="text-xs text-gray-500">{{ $c->plantacion->cultivo->nombre }}{{ $c->plantacion->nave ? ' · Nave '.$c->plantacion->nave->numero : '' }}</p>
            </td>
            <td class="text-right tabular-nums">{{ $kg($c->cantidad_kg) }}</td>
            <td class="text-right tabular-nums">{{ $kg($c->merma_kg) }}</td>
            <td class="text-right tabular-nums font-medium">{{ $kg($c->cantidad_aprovechable_kg) }}</td>
            <td>{{ $c->calidad?->nombre ?? '—' }}</td>
            <td class="text-right">@can('update', $c)<a href="{{ route('cosechas.edit', $c) }}" class="text-agro-moss font-medium hover:underline">Editar</a>@endcan</td>
        </tr>
    @endforeach
</x-table>
<div class="mt-4">{{ $cosechas->links() }}</div>
@endif
@endsection
