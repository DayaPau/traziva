<div class="bg-white rounded-lg shadow overflow-x-auto">
<table class="w-full text-sm">
    <thead class="bg-agro-green-100 text-left"><tr>
        <th class="p-2">Código</th><th>Cultivo</th><th>Nave</th><th>Siembra</th><th>Estado</th><th>Labores</th><th>Aplic.</th><th>Cosechas</th><th>IDC</th>
    </tr></thead>
    <tbody>
    @forelse($plantaciones as $p)
        <tr class="border-t">
            <td class="p-2"><a class="text-agro-primary underline" href="{{ route('plantaciones.show', $p) }}">{{ $p->codigo }}</a>@if($p->es_prueba) <span class="text-xs bg-yellow-100 px-1 rounded">prueba</span>@endif</td>
            <td>{{ $p->cultivo->nombre }}</td><td>{{ $p->nave?->numero ?? '—' }}</td><td>{{ $p->siembra_texto }}</td>
            <td>{{ $p->estado }}</td><td>{{ $p->labores_count }}</td><td>{{ $p->aplicaciones_count }}</td><td>{{ $p->cosechas_count }}</td>
            <td>{{ $p->ultimoIdc ? ($p->ultimoIdc->puntaje !== null ? $p->ultimoIdc->puntaje.' ('.$p->ultimoIdc->categoria.')' : $p->ultimoIdc->categoria) : '—' }}</td>
        </tr>
    @empty
        <tr><td colspan="9" class="p-4 text-center">Sin plantaciones. Ejecuta <code>php artisan traziva:importar database/data/FORMATO_PRODUCCION_CULTIVOS.xlsx</code></td></tr>
    @endforelse
    </tbody>
</table>
</div>
