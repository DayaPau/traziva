@php
    $colorEstado = ['activa' => 'verde', 'inactiva' => 'gris', 'finalizada' => 'tierra'];
@endphp
@if($plantaciones->isEmpty())
    <x-card>
        <x-empty-state titulo="Aún no hay plantaciones registradas">
            Créalas desde «Cultivos → Registrar nueva plantación». Para la carga inicial desde Excel usa
            <code class="text-xs">php artisan traziva:importar database/data/FORMATO_PRODUCCION_CULTIVOS.xlsx</code>
        </x-empty-state>
    </x-card>
@else
<x-table>
    <x-slot:head>
        <th>Código</th><th>Cultivo</th><th>Nave</th><th>Siembra</th><th>Estado</th>
        <th class="text-right">Labores</th><th class="text-right">Aplic.</th><th class="text-right">Cosechas</th><th>IDC</th>
    </x-slot:head>
    @foreach($plantaciones as $p)
        <tr class="hover:bg-agro-cream/60">
            <td class="whitespace-nowrap">
                <a class="font-mono text-xs text-agro-moss hover:underline" href="{{ route('plantaciones.show', $p) }}">{{ $p->codigo }}</a>
                @if($p->es_prueba) <x-badge color="azul" class="ml-1">Prueba</x-badge> @endif
            </td>
            <td class="font-medium">{{ $p->cultivo->nombre }}</td>
            <td>{{ $p->nave ? 'Nave '.$p->nave->numero : '—' }}</td>
            <td class="whitespace-nowrap">{{ $p->siembra_texto }}</td>
            <td><x-badge :color="$colorEstado[$p->estado] ?? 'gris'">{{ ucfirst($p->estado) }}</x-badge></td>
            <td class="text-right tabular-nums">{{ $p->labores_count }}</td>
            <td class="text-right tabular-nums">{{ $p->aplicaciones_count }}</td>
            <td class="text-right tabular-nums">{{ $p->cosechas_count }}</td>
            <td>@include('plantaciones._idc', ['idc' => $p->ultimoIdc])</td>
        </tr>
    @endforeach
</x-table>
@endif
