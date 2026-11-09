@extends('layouts.app')
@section('titulo', 'Gestión de cultivos')
@section('contenido')
<x-page-header titulo="Gestión de cultivos" subtitulo="Plantaciones registradas: cada una es un cultivo en una nave con su fecha de siembra.">
    @can('create', \App\Models\Plantacion::class)
        <a href="{{ route('plantaciones.create') }}" class="btn-primary"><x-icon name="mas" class="w-4 h-4" /> Registrar nueva plantación</a>
    @endcan
</x-page-header>

<form method="GET" class="card !p-4 mb-6 grid gap-3 md:grid-cols-[1fr_auto_auto_auto_auto]">
    <div class="relative">
        <x-icon name="buscar" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" />
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por cultivo, código, variedad o nave" class="input-modern !pl-10" aria-label="Buscar">
    </div>
    <select name="cultivo_id" class="input-modern" onchange="this.form.submit()" aria-label="Cultivo">
        <option value="">Todos los cultivos</option>
        @foreach($cultivos as $id => $nombre)<option value="{{ $id }}" @selected(request('cultivo_id') == $id)>{{ $nombre }}</option>@endforeach
    </select>
    <select name="estado" class="input-modern" onchange="this.form.submit()" aria-label="Estado">
        <option value="">Todos los estados</option>
        @foreach(\App\Models\Plantacion::ESTADOS as $v => $t)<option value="{{ $v }}" @selected(request('estado') === $v)>{{ $t }}</option>@endforeach
    </select>
    <select name="datos" class="input-modern" onchange="this.form.submit()" aria-label="Origen de datos">
        <option value="">Reales y de prueba</option>
        <option value="reales" @selected(request('datos') === 'reales')>Solo datos reales</option>
        <option value="prueba" @selected(request('datos') === 'prueba')>Solo datos de prueba</option>
    </select>
    <button class="btn-secondary">Buscar</button>
</form>

@if($plantaciones->isEmpty())
    <x-card>
        <x-empty-state titulo="{{ request()->hasAny(['q', 'estado', 'cultivo_id', 'datos']) ? 'Ninguna plantación coincide con los filtros' : 'Aún no hay plantaciones registradas' }}">
            @can('create', \App\Models\Plantacion::class) Usa «Registrar nueva plantación» para crear la primera. @endcan
        </x-empty-state>
    </x-card>
@else
<x-table>
    <x-slot:head>
        <th>Código</th><th>Cultivo</th><th>Nave</th><th>Siembra</th><th class="hidden xl:table-cell">Responsable</th><th>Estado / etapa</th><th>IDC</th><th class="text-right">Acciones</th>
    </x-slot:head>
    @foreach($plantaciones as $p)
        <tr class="hover:bg-agro-cream/60">
            <td class="whitespace-nowrap">
                <span class="font-mono text-xs text-gray-600">{{ $p->codigo }}</span>
                @if($p->es_prueba)<x-badge color="azul" class="ml-1">Prueba</x-badge>@endif
            </td>
            <td>
                <p class="font-semibold">{{ $p->cultivo->nombre }}</p>
                @if($p->variedad)<p class="text-xs text-gray-500">{{ $p->variedad }}</p>@endif
            </td>
            <td class="whitespace-nowrap">{{ $p->nave ? 'Nave '.$p->nave->numero : '—' }}</td>
            <td class="whitespace-nowrap">{{ $p->siembra_texto }}</td>
            <td class="hidden xl:table-cell">{{ $p->responsable?->name ?? '—' }}</td>
            <td>@include('plantaciones._insignias')</td>
            <td>@include('plantaciones._idc', ['idc' => $p->ultimoIdc])</td>
            <td>
                <div class="flex items-center justify-end gap-1">
                    <a href="{{ route('plantaciones.show', $p) }}" class="p-1.5 rounded-lg text-gray-600 hover:bg-gray-100" title="Ver detalle" aria-label="Ver {{ $p->codigo }}"><x-icon name="ojo" class="w-[18px] h-[18px]" /></a>
                    @can('update', $p)
                        <a href="{{ route('plantaciones.edit', $p) }}" class="p-1.5 rounded-lg text-gray-600 hover:bg-gray-100" title="Editar" aria-label="Editar {{ $p->codigo }}"><x-icon name="lapiz" class="w-[18px] h-[18px]" /></a>
                        {{-- Cambio rápido de estado y etapa (S5-09). Queda auditado; la plantación nunca se borra. --}}
                        <div class="relative" x-data="{ abierto: false }" @click.outside="abierto = false" @keydown.escape="abierto = false">
                            <button type="button" @click="abierto = !abierto" :aria-expanded="abierto" class="rounded-lg border border-gray-200 px-2.5 py-1 text-xs text-gray-700 hover:bg-gray-50">
                                Estado ▾
                            </button>
                            <form x-cloak x-show="abierto" x-transition method="POST" action="{{ route('plantaciones.estado', $p) }}"
                                  class="absolute right-0 z-10 mt-2 w-56 space-y-3 rounded-xl border border-gray-200 bg-white p-4 text-left shadow-lg">
                                @csrf @method('PATCH')
                                <label class="block text-xs font-medium text-gray-600">Estado
                                    <select name="estado" class="input-modern !py-1.5 mt-1">
                                        @foreach(\App\Models\Plantacion::ESTADOS as $v => $t)<option value="{{ $v }}" @selected($p->estado === $v)>{{ $t }}</option>@endforeach
                                    </select>
                                </label>
                                <label class="block text-xs font-medium text-gray-600">Etapa
                                    <select name="etapa" class="input-modern !py-1.5 mt-1">
                                        <option value="">Sin definir</option>
                                        @foreach(\App\Models\Plantacion::ETAPAS as $v => $t)<option value="{{ $v }}" @selected($p->etapa === $v)>{{ $t }}</option>@endforeach
                                    </select>
                                </label>
                                <button class="btn-primary w-full !py-1.5">Guardar</button>
                            </form>
                        </div>
                    @endcan
                </div>
            </td>
        </tr>
    @endforeach
</x-table>
<div class="mt-4">{{ $plantaciones->links() }}</div>
@endif
@endsection
