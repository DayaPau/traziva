@extends('layouts.invitado')
@section('titulo', 'Solicitar acceso')
@section('contenido')
<h1>Solicitar acceso</h1>
<p class="text-sm text-gray-500 mt-1 mb-6">Tu cuenta quedará <strong>pendiente</strong> hasta que el/la coordinador/a de Amanecer la apruebe.</p>

<form method="POST" action="{{ url('/registro') }}" class="space-y-4" novalidate>
    @csrf
    <x-input name="name" label="Nombre completo" required autocomplete="name" maxlength="120" />
    <x-input name="email" label="Correo electrónico" type="email" required autocomplete="email" maxlength="150" />
    <x-select name="rol_id" label="Rol que solicitas" :opciones="$roles" vacio="Selecciona tu rol…" required />
    <x-input name="password" label="Contraseña" type="password" required autocomplete="new-password" ayuda="Mínimo 8 caracteres, con letras y números." />
    <x-input name="password_confirmation" label="Confirmar contraseña" type="password" required autocomplete="new-password" />
    <button class="btn-primary w-full">Enviar solicitud</button>
</form>

<p class="text-sm text-gray-600 mt-6 text-center">
    ¿Ya tienes cuenta? <a href="{{ route('login') }}" class="font-semibold text-agro-moss hover:underline">Ingresar</a>
</p>
@endsection
