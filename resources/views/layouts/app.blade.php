<!DOCTYPE html>
<html lang="es-EC">
<head>
    <title>@hasSection('titulo')@yield('titulo') · @endif Traziva</title>
    @include('layouts._head')
</head>
@php
    $usuario = auth()->user();
    // El menú se filtra por rol desde config/traziva.php (fuente única). Ocultar no basta:
    // cada ruta además está protegida por middleware y Policies.
    $menu = collect(config('traziva.menu'))
        ->filter(fn ($i) => $i['roles'] === null || $usuario->tieneRol(...$i['roles']));
@endphp
<body class="min-h-screen" x-data="{ menuAbierto: false }" @keydown.escape.window="menuAbierto = false">

{{-- Fondo oscuro detrás del menú en celulares --}}
<div x-cloak x-show="menuAbierto" x-transition.opacity @click="menuAbierto = false" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

{{-- Barra lateral: fija en pantallas grandes, desplegable con ☰ en celulares --}}
<aside class="fixed inset-y-0 left-0 z-40 w-64 bg-agro-forest text-white flex flex-col transition-transform duration-200 -translate-x-full lg:translate-x-0"
       :class="{ 'translate-x-0': menuAbierto, '-translate-x-full': !menuAbierto }">
    <div class="flex items-center justify-between px-5 py-5 border-b border-white/10">
        <a href="{{ route('dashboard') }}"><x-logo /></a>
        <button type="button" class="lg:hidden text-white/80" @click="menuAbierto = false" aria-label="Cerrar menú"><x-icon name="cerrar" /></button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1" aria-label="Menú principal">
        @foreach($menu as $item)
            @if($item['ruta'])
                @php $activo = request()->routeIs($item['activo']); @endphp
                <a href="{{ route($item['ruta'], $item['params'] ?? []) }}" @if($activo) aria-current="page" @endif
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors {{ $activo ? 'bg-agro-leaf text-white font-medium' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <x-icon :name="$item['icono']" class="w-[18px] h-[18px]" /> {{ $item['texto'] }}
                </a>
            @else
                <span class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-white/40 cursor-not-allowed" title="Disponible en una próxima fase">
                    <x-icon :name="$item['icono']" class="w-[18px] h-[18px]" /> {{ $item['texto'] }}
                    <span class="ml-auto text-[10px] uppercase tracking-wide">Próx.</span>
                </span>
            @endif
        @endforeach
    </nav>

    <div class="px-3 pb-4 pt-3 border-t border-white/10 space-y-2">
        <div class="rounded-xl bg-white/10 px-3 py-2.5">
            <p class="text-sm font-medium truncate">{{ $usuario->name }}</p>
            <p class="text-xs text-white/70 truncate">{{ $usuario->rol_etiqueta }}</p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="w-full flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-white/80 hover:bg-white/10 hover:text-white">
                <x-icon name="salir" class="w-[18px] h-[18px]" /> Cerrar sesión
            </button>
        </form>
    </div>
</aside>

<div class="lg:pl-64 min-h-screen flex flex-col">
    {{-- Cabecera superior --}}
    <header class="sticky top-0 z-20 bg-white/90 backdrop-blur border-b border-gray-200">
        <div class="flex items-center gap-3 px-4 md:px-8 h-16">
            <button type="button" class="lg:hidden -ml-1 p-1.5 rounded-lg text-gray-600 hover:bg-gray-100" @click="menuAbierto = true" aria-label="Abrir menú">
                <x-icon name="menu" class="w-6 h-6" />
            </button>
            <span class="text-sm text-gray-600 truncate min-w-0">Amanecer Biocentro Académico</span>
            <div class="ml-auto flex items-center gap-3 min-w-0">
                <span class="hidden sm:block text-sm text-gray-700 truncate">{{ $usuario->name }}</span>
                <x-badge color="verde" title="{{ $usuario->rol_etiqueta }}"><span class="sm:hidden">{{ ucfirst($usuario->rol?->nombre) }}</span><span class="hidden sm:inline">{{ $usuario->rol_etiqueta }}</span></x-badge>
            </div>
        </div>
    </header>

    <main class="flex-1 px-4 md:px-8 py-6 md:py-8 max-w-7xl w-full">
        @if(session('ok') || $errors->any())
            <div class="space-y-3 mb-6">
                @if(session('ok'))<x-alert tipo="exito">{{ session('ok') }}</x-alert>@endif
                @if($errors->any())
                    <x-alert tipo="error">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</x-alert>
                @endif
            </div>
        @endif
        @yield('contenido')
    </main>
</div>
</body>
</html>
