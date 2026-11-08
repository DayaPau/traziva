{{-- Estado vacío claro (nunca datos inventados). --}}
@props(['titulo', 'icono' => 'brote'])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center py-10 px-4']) }}>
    <div class="w-12 h-12 rounded-full bg-agro-cream flex items-center justify-center text-agro-moss mb-3">
        <x-icon :name="$icono" class="w-6 h-6" />
    </div>
    <p class="font-medium text-gray-800">{{ $titulo }}</p>
    @if(trim($slot))<div class="text-sm text-gray-500 mt-1 max-w-md">{{ $slot }}</div>@endif
</div>
