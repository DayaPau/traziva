<!DOCTYPE html>
<html lang="es-EC">
<head>
    <title>@yield('titulo') · Traziva</title>
    @include('layouts._head')
</head>
<body class="min-h-screen lg:grid lg:grid-cols-2">
    {{-- Panel de marca (solo pantallas grandes) --}}
    <aside class="hidden lg:flex flex-col justify-between bg-agro-forest text-white p-12">
        <x-logo />
        <div class="max-w-md">
            <p class="font-display text-3xl leading-snug">Registros de campo organizados, históricos y útiles para decidir.</p>
            <p class="mt-4 text-white/70 text-sm">Plantaciones, labores culturales, aplicaciones, cosechas e indicadores de desempeño en un solo lugar.</p>
        </div>
        <p class="text-xs text-white/50">Amanecer Biocentro Académico · Universidad de Las Américas</p>
    </aside>

    <main class="flex items-center justify-center px-4 py-10 sm:px-8">
        <div class="w-full max-w-md">
            <x-logo :claro="false" class="lg:hidden mb-8 justify-center" />
            @yield('contenido')
        </div>
    </main>
</body>
</html>
