@extends('layouts.invitado')
@section('titulo', 'Crear cuenta')
@section('contenido')
<h1>Crear cuenta</h1>
<p class="text-sm text-gray-500 mt-1 mb-6">
    Docentes y estudiantes entran de inmediato. Para coordinador/a o responsable de producción necesitas el código de verificación de tu rol.
</p>

<form method="POST" action="{{ url('/registro') }}" class="space-y-4" novalidate
      x-data="{ rol: @js((string) old('rol_id', '')), conCodigo: @js(array_map('strval', $conCodigo)) }">
    @csrf

    {{-- Campo trampa contra bots: oculto para las personas --}}
    <div class="hidden" aria-hidden="true">
        <label for="sitio_web">No llenar este campo</label>
        <input id="sitio_web" name="sitio_web" type="text" tabindex="-1" autocomplete="off">
    </div>

    <x-input name="name" label="Nombre completo" required autocomplete="name" minlength="3" maxlength="120" />
    <x-input name="email" label="Correo electrónico" type="email" required autocomplete="email" maxlength="150" />
    <x-select name="rol_id" label="Rol" :opciones="$roles" vacio="Selecciona tu rol…" required x-model="rol" />

    <div x-show="conCodigo.includes(rol)" x-cloak>
        <x-input name="codigo" label="Código de verificación del rol" autocomplete="off" maxlength="60"
                 ayuda="Lo entrega el/la coordinador/a de Amanecer. Sin él no se puede crear una cuenta de este rol." />
    </div>

    {{-- Contraseña con requisitos visibles que se marcan al cumplirse --}}
    <div x-data="{ c: '' }">
        <x-input name="password" label="Contraseña" type="password" required autocomplete="new-password" x-model="c" />
        <ul class="mt-2 grid grid-cols-2 gap-x-4 gap-y-1 text-xs" aria-label="Requisitos de la contraseña">
            @foreach([
                'c.length >= 8' => 'Mínimo 8 caracteres',
                '/[a-z]/.test(c) && /[A-Z]/.test(c)' => 'Mayúsculas y minúsculas',
                '/[0-9]/.test(c)' => 'Al menos un número',
                '/[^A-Za-z0-9]/.test(c)' => 'Al menos un símbolo (!@#…)',
            ] as $condicion => $texto)
                <li class="flex items-center gap-1.5" :class="{{ $condicion }} ? 'text-green-700' : 'text-gray-500'">
                    <span x-text="{{ $condicion }} ? '✓' : '○'"></span> {{ $texto }}
                </li>
            @endforeach
        </ul>
    </div>
    <x-input name="password_confirmation" label="Confirmar contraseña" type="password" required autocomplete="new-password" />

    <div>
        <label class="flex items-start gap-2 text-sm text-gray-700">
            <input type="checkbox" name="terminos" value="1" @checked(old('terminos')) required
                   class="mt-0.5 rounded border-gray-300 text-agro-moss focus:ring-agro-moss">
            <span>Acepto que Traziva guarde mis datos (nombre, correo y rol) solo para el uso de la plataforma en Amanecer Biocentro Académico.</span>
        </label>
        @error('terminos')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <button class="btn-primary w-full">Crear cuenta</button>
</form>

<p class="text-sm text-gray-600 mt-6 text-center">
    ¿Ya tienes cuenta? <a href="{{ route('login') }}" class="font-semibold text-agro-moss hover:underline">Ingresar</a>
</p>
@endsection
