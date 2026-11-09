@extends('layouts.app')
@php
    $editando = $u->exists;
    $esYo = $editando && $u->is(auth()->user());
    $estados = collect(\App\Models\User::ESTADOS)->only($u->estado === 'pendiente' ? ['pendiente', 'activo', 'inactivo'] : ['activo', 'inactivo'])->all();
@endphp
@section('titulo', $editando ? "Editar {$u->name}" : 'Registrar usuario')
@section('contenido')
<x-page-header :titulo="$editando ? 'Editar usuario' : 'Registrar usuario'" :subtitulo="$editando ? $u->email : 'La cuenta queda activa de inmediato.'">
    <a href="{{ route('usuarios.index') }}" class="btn-secondary">Volver</a>
</x-page-header>

<div class="grid gap-6 lg:grid-cols-3">
    <x-card :titulo="$editando ? 'Datos y acceso' : 'Datos de la cuenta'" class="lg:col-span-2">
        @if($editando && $u->estado === 'pendiente')
            <x-alert tipo="aviso" class="mb-5">Solicitud pendiente. Revisa el rol y, si corresponde, fija una fecha de fin del acceso; luego cambia el estado a «Activo» para aprobarla.</x-alert>
        @endif
        @if($esYo)
            <x-alert tipo="info" class="mb-5">Estás editando tu propia cuenta: no puedes cambiar tu rol ni desactivarte.</x-alert>
        @endif

        <form method="POST" action="{{ $editando ? route('usuarios.update', $u) : route('usuarios.store') }}" class="space-y-5">
            @csrf
            @if($editando) @method('PUT') @endif
            <div class="grid gap-5 md:grid-cols-2">
                <x-input name="name" label="Nombre completo *" :value="$u->name" required maxlength="120" />
                <x-input name="email" label="Correo electrónico *" type="email" :value="$u->email" required maxlength="150" />
                <x-select name="rol_id" label="Rol *" :opciones="$roles" :valor="$u->rol_id" vacio="Selecciona…" required :disabled="$esYo" />
                @if($editando)
                    <x-select name="estado" label="Estado *" :opciones="$estados" :valor="$u->estado" :disabled="$esYo" />
                @endif
                <x-input name="activo_desde" label="Acceso desde" type="date" :value="$u->activo_desde?->format('Y-m-d')" ayuda="Opcional." />
                <x-input name="activo_hasta" label="Acceso hasta" type="date" :value="$u->activo_hasta?->format('Y-m-d')"
                         ayuda="Para accesos temporales (p. ej. estudiantes en práctica). Vacío = sin vencimiento." />
                @unless($editando)
                    <x-input name="password" label="Contraseña inicial *" type="password" required autocomplete="new-password" ayuda="Mínimo 8 caracteres, con letras y números." />
                    <x-input name="password_confirmation" label="Confirmar contraseña *" type="password" required autocomplete="new-password" />
                @endunless
            </div>
            @if($esYo)
                {{-- Los campos deshabilitados no se envían: se mandan los valores actuales. --}}
                <input type="hidden" name="rol_id" value="{{ $u->rol_id }}">
                <input type="hidden" name="estado" value="{{ $u->estado }}">
            @endif
            <div class="flex gap-3 pt-2">
                <button class="btn-primary">{{ $editando ? 'Guardar cambios' : 'Registrar usuario' }}</button>
                <a href="{{ route('usuarios.index') }}" class="btn-secondary">Cancelar</a>
            </div>
        </form>
    </x-card>

    @if($editando)
        <x-card titulo="Restablecer contraseña" subtitulo="Fija una contraseña nueva y comunícasela por un canal seguro. No se guarda en la auditoría.">
            <form method="POST" action="{{ route('usuarios.clave', $u) }}" class="space-y-4">
                @csrf @method('PUT')
                <x-input name="password" label="Nueva contraseña" type="password" required autocomplete="new-password" ayuda="Mínimo 8 caracteres, con letras y números." />
                <x-input name="password_confirmation" label="Confirmar" type="password" required autocomplete="new-password" />
                <button class="btn-secondary w-full">Restablecer contraseña</button>
            </form>
        </x-card>
    @endif
</div>
@endsection
