@extends('layouts.app')
@section('contenido')
<h1 class="mb-4">Usuarios</h1>
<form method="POST" action="{{ route('usuarios.store') }}" class="bg-white rounded-lg shadow p-4 mb-6 grid md:grid-cols-6 gap-2 text-sm">@csrf
    <input name="name" placeholder="Nombre" required class="border rounded px-2 py-1">
    <input name="email" type="email" placeholder="Correo" required class="border rounded px-2 py-1">
    <input name="password" type="password" placeholder="Contraseña (mín. 8)" required class="border rounded px-2 py-1">
    <select name="rol_id" class="border rounded px-2 py-1">@foreach($roles as $r)<option value="{{ $r->id }}">{{ $r->nombre }}</option>@endforeach</select>
    <input name="activo_hasta" type="date" title="Acceso temporal (opcional)" class="border rounded px-2 py-1">
    <button class="btn-primary">Crear</button>
</form>
<div class="bg-white rounded-lg shadow"><table class="w-full text-sm"><thead><tr class="text-left"><th class="p-2">Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Vence</th><th></th></tr></thead><tbody>
@foreach($usuarios as $u)
<tr class="border-t"><td class="p-2">{{ $u->name }}</td><td>{{ $u->email }}</td><td>{{ $u->rol->nombre }}</td><td>{{ $u->estado }}</td><td>{{ $u->activo_hasta?->format('d/m/Y') ?? '—' }}</td>
<td>@if($u->id !== auth()->id())<form method="POST" action="{{ route('usuarios.toggle', $u) }}">@csrf @method('PATCH')<button class="underline">{{ $u->estado === 'activo' ? 'Desactivar' : 'Activar' }}</button></form>@endif</td></tr>
@endforeach
</tbody></table></div>
@endsection
