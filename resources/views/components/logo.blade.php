{{-- Marca Traziva. :claro = texto blanco (barra lateral). --}}
@props(['claro' => true])
<div {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    <span class="w-10 h-10 rounded-xl bg-agro-leaf text-white flex items-center justify-center"><x-icon name="brote" class="w-6 h-6" /></span>
    <span class="leading-tight">
        <span class="block font-display text-lg font-semibold {{ $claro ? 'text-white' : 'text-agro-forest' }}">Traziva</span>
        <span class="block text-xs {{ $claro ? 'text-white/70' : 'text-gray-500' }}">Trazabilidad de cultivos</span>
    </span>
</div>
