@extends('layouts.app')
@section('titulo', 'Usuarios y roles')
@section('contenido')
@php $pendientes = $usuarios->where('estado', 'pendiente')->count(); @endphp
<x-page-header titulo="Usuarios y roles" subtitulo="Cuentas, roles y accesos temporales. La gestión completa llega en la Fase 6." />

@if($pendientes)
    <x-alert tipo="aviso" class="mb-6">Hay {{ $pendientes }} {{ $pendientes === 1 ? 'solicitud pendiente' : 'solicitudes pendientes' }} de aprobación. Revisa el rol solicitado antes de aprobar.</x-alert>
@endif

<x-card titulo="Crear usuario" class="mb-6">
    <form method="POST" action="{{ route('usuarios.store') }}" class="grid gap-4 md:grid-cols-3 lg:grid-cols-6 items-end">
        @csrf
        <x-input name="name" label="Nombre" required />
        <x-input name="email" label="Correo" type="email" required />
        <x-input name="password" label="Contraseña" type="password" required minlength="8" />
        <x-select name="rol_id" label="Rol" :opciones="$roles" />
        <x-input name="activo_hasta" label="Acceso hasta" type="date" ayuda="Opcional (temporal)" />
        <button class="btn-primary">Crear</button>
    </form>
</x-card>

<x-table>
    <x-slot:head><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Vence</th><th class="text-right">Acciones</th></x-slot:head>
    @foreach($usuarios as $u)
        <tr>
            <td class="font-medium">{{ $u->name }}</td>
            <td class="text-gray-600">{{ $u->email }}</td>
            <td>{{ $u->rol_etiqueta }}</td>
            <td>
                @switch($u->estado)
                    @case('pendiente') <x-badge color="amarillo">Pendiente</x-badge> @break
                    @case('activo') <x-badge color="verde">Activo</x-badge> @break
                    @default <x-badge>Inactivo</x-badge>
                @endswitch
            </td>
            <td>{{ $u->activo_hasta?->format('d/m/Y') ?? '—' }}</td>
            <td class="text-right">
                @if($u->id !== auth()->id())
                    <form method="POST" action="{{ route('usuarios.toggle', $u) }}" class="inline">
                        @csrf @method('PATCH')
                        <button class="{{ $u->estado === 'activo' ? 'btn-secondary' : 'btn-primary' }} !py-1.5 !px-3">
                            {{ ['pendiente' => 'Aprobar', 'activo' => 'Desactivar'][$u->estado] ?? 'Activar' }}
                        </button>
                    </form>
                @endif
            </td>
        </tr>
    @endforeach
</x-table>
@endsection
