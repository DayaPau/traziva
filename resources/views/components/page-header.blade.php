{{-- Título de página + subtítulo + acciones (p. ej. botón "+ Registrar nuevo"). --}}
@props(['titulo', 'subtitulo' => null])
<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1>{{ $titulo }}</h1>
        @if($subtitulo)<p class="text-sm text-gray-500 mt-1">{{ $subtitulo }}</p>@endif
    </div>
    @if(trim($slot))<div class="flex flex-wrap gap-2">{{ $slot }}</div>@endif
</div>
