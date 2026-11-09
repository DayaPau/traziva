@extends('layouts.app')
@section('titulo', $def['titulo'])
@section('contenido')
<x-page-header :titulo="$def['titulo']" :subtitulo="$def['descripcion']">
    <a href="{{ route('catalogos.create', $catalogo) }}" class="btn-primary"><x-icon name="mas" class="w-4 h-4" /> Registrar {{ $def['singular'] }}</a>
</x-page-header>

@include('catalogos._nav')

@isset($def['provisional'])
    <x-alert tipo="aviso" class="mb-6"><strong>Provisional.</strong> {{ $def['provisional'] }}</x-alert>
@endisset

<form method="GET" class="card !p-4 mb-6 flex flex-col sm:flex-row gap-3">
    <div class="relative flex-1">
        <x-icon name="buscar" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" />
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por {{ mb_strtolower($def['campos'][$campoNombre]['label']) }}…" class="input-modern !pl-10" aria-label="Buscar">
    </div>
    <select name="estado" class="input-modern sm:!w-48" onchange="this.form.submit()" aria-label="Filtrar por estado">
        <option value="">Todos</option>
        <option value="activos" @selected(request('estado') === 'activos')>Activos</option>
        <option value="inactivos" @selected(request('estado') === 'inactivos')>Inactivos</option>
    </select>
    <button class="btn-secondary">Buscar</button>
</form>

@if($registros->isEmpty())
    <x-card><x-empty-state titulo="No hay registros que coincidan">Prueba con otra búsqueda o registra uno nuevo.</x-empty-state></x-card>
@else
<x-table>
    <x-slot:head>
        @foreach($def['campos'] as $c)<th>{{ $c['label'] }}</th>@endforeach
        <th class="text-right">En uso</th><th>Estado</th><th class="text-right">Acciones</th>
    </x-slot:head>
    @foreach($registros as $r)
        <tr class="{{ $r->activo ? '' : 'text-gray-400' }}">
            @foreach($def['campos'] as $campo => $c)
                <td @class(['font-medium' => $loop->first])>
                    @switch($c['tipo'])
                        @case('select') {{ $c['opciones'][$r->$campo] ?? '—' }} @break
                        @case('relacion') {{ $r->$campo->pluck($c['texto'])->join(', ') ?: '—' }} @break
                        @default {{ $r->$campo ?? '—' }}
                    @endswitch
                </td>
            @endforeach
            <td class="text-right tabular-nums">{{ $r->{$def['conteo'].'_count'} }}</td>
            <td>@if($r->activo)<x-badge color="verde">Activo</x-badge>@else<x-badge>Inactivo</x-badge>@endif</td>
            <td class="text-right whitespace-nowrap">
                <a href="{{ route('catalogos.edit', [$catalogo, $r->id]) }}" class="text-agro-moss font-medium hover:underline mr-3">Editar</a>
                <form method="POST" action="{{ route('catalogos.toggle', [$catalogo, $r->id]) }}" class="inline">
                    @csrf @method('PATCH')
                    <button class="text-gray-600 hover:underline">{{ $r->activo ? 'Desactivar' : 'Activar' }}</button>
                </form>
            </td>
        </tr>
    @endforeach
</x-table>
@endif
@endsection
