{{-- Tabla con encabezado en mayúsculas y filas separadas por líneas suaves.
     Uso: <x-table><x-slot:head><th>…</th></x-slot:head> <tr>…</tr> </x-table> --}}
<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-gray-200/70 shadow-sm overflow-x-auto lg:overflow-visible']) }}>
    <table class="w-full text-sm">
        <thead class="text-left text-xs uppercase tracking-wide text-gray-500 border-b border-gray-200">
            <tr class="[&>th]:px-3 [&>th]:py-3 [&>th]:font-medium">{{ $head }}</tr>
        </thead>
        <tbody class="divide-y divide-gray-100 [&>tr>td]:px-3 [&>tr>td]:py-3 [&>tr>td]:align-middle">
            {{ $slot }}
        </tbody>
    </table>
</div>
