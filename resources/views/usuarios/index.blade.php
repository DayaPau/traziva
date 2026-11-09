@extends('layouts.app')
@section('titulo', 'Usuarios y roles')
@section('contenido')
<x-page-header titulo="Usuarios y roles" subtitulo="Cuentas, roles y accesos temporales. Solo el/la coordinador/a administra usuarios.">
    <a href="{{ route('usuarios.create') }}" class="btn-primary"><x-icon name="mas" class="w-4 h-4" /> Registrar usuario</a>
</x-page-header>

@if($pendientes)
    <x-alert tipo="aviso" class="mb-6">
        Hay {{ $pendientes }} {{ $pendientes === 1 ? 'solicitud pendiente' : 'solicitudes pendientes' }} de aprobación.
        Revisa el rol solicitado (puedes cambiarlo con «Editar») antes de aprobar.
    </x-alert>
@endif

<form method="GET" class="card !p-4 mb-6 grid gap-3 md:grid-cols-[1fr_auto_auto_auto]">
    <div class="relative">
        <x-icon name="buscar" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" />
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o correo" class="input-modern !pl-10" aria-label="Buscar">
    </div>
    <select name="rol_id" class="input-modern" onchange="this.form.submit()" aria-label="Rol">
        <option value="">Todos los roles</option>
        @foreach($roles as $id => $nombre)<option value="{{ $id }}" @selected(request('rol_id') == $id)>{{ $nombre }}</option>@endforeach
    </select>
    <select name="estado" class="input-modern" onchange="this.form.submit()" aria-label="Estado">
        <option value="">Todos los estados</option>
        @foreach(\App\Models\User::ESTADOS as $v => $t)<option value="{{ $v }}" @selected(request('estado') === $v)>{{ $t }}</option>@endforeach
    </select>
    <button class="btn-secondary">Buscar</button>
</form>

@if($usuarios->isEmpty())
    <x-card><x-empty-state titulo="Ningún usuario coincide con los filtros" icono="usuarios" /></x-card>
@else
<x-table>
    <x-slot:head><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acceso</th><th class="text-right">Acciones</th></x-slot:head>
    @foreach($usuarios as $u)
        @php $vigente = $u->estaVigente(); @endphp
        <tr>
            <td class="font-medium">{{ $u->name }} @if($u->id === auth()->id())<span class="text-xs text-gray-500">(tú)</span>@endif</td>
            <td class="text-gray-600">{{ $u->email }}</td>
            <td>{{ $u->rol_etiqueta }}</td>
            <td>
                @switch($u->estado)
                    @case('pendiente') <x-badge color="amarillo">Pendiente</x-badge> @break
                    @case('activo') <x-badge :color="$vigente ? 'verde' : 'rojo'">{{ $vigente ? 'Activo' : 'Fuera de vigencia' }}</x-badge> @break
                    @default <x-badge>Inactivo</x-badge>
                @endswitch
            </td>
            <td class="whitespace-nowrap text-sm">
                @if($u->activo_desde || $u->activo_hasta)
                    {{ $u->activo_desde?->format('d/m/Y') ?? '…' }} – {{ $u->activo_hasta?->format('d/m/Y') ?? '…' }}
                @else
                    <span class="text-gray-400">Permanente</span>
                @endif
            </td>
            <td class="text-right whitespace-nowrap">
                <a href="{{ route('usuarios.edit', $u) }}" class="text-agro-moss font-medium hover:underline mr-3">Editar</a>
                @if($u->id !== auth()->id())
                    <form method="POST" action="{{ route('usuarios.toggle', $u) }}" class="inline">
                        @csrf @method('PATCH')
                        <button class="{{ $u->estado === 'pendiente' ? 'btn-primary !py-1.5 !px-3' : 'text-gray-600 hover:underline' }}">
                            {{ ['pendiente' => 'Aprobar', 'activo' => 'Desactivar'][$u->estado] ?? 'Activar' }}
                        </button>
                    </form>
                @endif
            </td>
        </tr>
    @endforeach
</x-table>
@endif
@endsection
