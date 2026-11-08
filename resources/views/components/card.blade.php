{{-- Tarjeta blanca redondeada. Uso: <x-card titulo="Producción">…</x-card> --}}
@props(['titulo' => null, 'subtitulo' => null])
<section {{ $attributes->merge(['class' => 'card']) }}>
    @if($titulo || isset($acciones))
        <header class="flex flex-wrap items-start justify-between gap-3 mb-4">
            <div>
                @if($titulo)<h2>{{ $titulo }}</h2>@endif
                @if($subtitulo)<p class="text-sm text-gray-500 mt-0.5">{{ $subtitulo }}</p>@endif
            </div>
            {{ $acciones ?? '' }}
        </header>
    @endif
    {{ $slot }}
</section>
