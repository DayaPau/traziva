<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Traziva')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
<nav class="bg-agro-primary text-white">
    <div class="max-w-6xl mx-auto px-4 py-3 flex flex-wrap items-center gap-4">
        <a href="{{ route('dashboard') }}" class="font-bold text-xl text-white">Traziva</a>
        <a href="{{ route('dashboard') }}" class="hover:underline">Inicio</a>
        <a href="{{ route('plantaciones.index') }}" class="hover:underline">Plantaciones</a>
        @if(auth()->user()->tieneRol('coordinador','responsable'))
            <a href="{{ route('plantaciones.exportar') }}" class="hover:underline">Exportar CSV</a>
        @endif
        @if(auth()->user()->esCoordinador())
            <a href="{{ route('usuarios.index') }}" class="hover:underline">Usuarios</a>
        @endif
        <span class="ml-auto text-sm">{{ auth()->user()->name }} · {{ auth()->user()->rol->nombre }}</span>
        <form method="POST" action="{{ route('logout') }}">@csrf <button class="underline text-sm">Salir</button></form>
    </div>
</nav>
<main class="max-w-6xl mx-auto px-4 py-6">
    @if(session('ok'))<div class="mb-4 p-3 rounded bg-green-100 text-green-900">{{ session('ok') }}</div>@endif
    @if($errors->any())<div class="mb-4 p-3 rounded bg-red-100 text-red-900">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
    @yield('contenido')
</main>
</body>
</html>
