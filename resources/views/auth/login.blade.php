@extends('layouts.invitado')
@section('titulo', 'Ingresar')
@section('contenido')
<h1>Ingresar</h1>
<p class="text-sm text-gray-500 mt-1 mb-6">Usa el correo y la contraseña de tu cuenta de Traziva.</p>

@if(session('ok'))<x-alert tipo="exito" class="mb-4">{{ session('ok') }}</x-alert>@endif
@if($errors->any())<x-alert tipo="error" class="mb-4">{{ $errors->first() }}</x-alert>@endif

<form method="POST" action="{{ url('/login') }}" class="space-y-4" novalidate>
    @csrf
    <div>
        <label for="email" class="label">Correo electrónico</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="input-modern">
    </div>
    <div>
        <label for="password" class="label">Contraseña</label>
        <input id="password" name="password" type="password" required autocomplete="current-password" class="input-modern">
    </div>
    <label class="flex items-center gap-2 text-sm text-gray-600">
        <input type="checkbox" name="remember" value="1" class="rounded border-gray-300 text-agro-moss focus:ring-agro-moss"> Mantener la sesión en este equipo
    </label>
    <button class="btn-primary w-full">Ingresar</button>
</form>

<p class="text-sm text-gray-600 mt-6 text-center">
    ¿No tienes cuenta? <a href="{{ route('registro') }}" class="font-semibold text-agro-moss hover:underline">Solicitar acceso</a>
</p>
@endsection
