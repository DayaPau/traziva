<!DOCTYPE html>
<html lang="es-EC">
<head>
    <title>{{ $codigo }} · Traziva</title>
    @include('layouts._head')
</head>
<body class="min-h-screen flex items-center justify-center px-4">
    <div class="card max-w-md w-full text-center">
        <x-logo :claro="false" class="justify-center mb-6" />
        <p class="text-sm font-semibold text-agro-moss">Error {{ $codigo }}</p>
        <h1 class="mt-1">{{ $titulo }}</h1>
        <p class="text-sm text-gray-600 mt-3">{{ $mensaje }}</p>
        <a href="{{ url('/dashboard') }}" class="btn-primary mt-6">Volver al inicio</a>
    </div>
</body>
</html>
