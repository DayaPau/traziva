{{-- Pestañas para moverse entre catálogos --}}
<nav class="flex gap-2 overflow-x-auto pb-1 mb-6 -mx-1 px-1" aria-label="Catálogos">
    @foreach(\App\Support\Catalogos::todos() as $slug => $c)
        <a href="{{ route('catalogos.index', $slug) }}" @if($slug === $catalogo) aria-current="page" @endif
           class="whitespace-nowrap rounded-full px-3.5 py-1.5 text-sm border transition-colors {{ $slug === $catalogo ? 'bg-agro-moss text-white border-agro-moss' : 'bg-white text-gray-700 border-gray-200 hover:border-agro-moss' }}">
            {{ $c['titulo'] }}
        </a>
    @endforeach
</nav>
