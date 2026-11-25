@extends('layouts.app')
@section('titulo', 'Índice de Desempeño')
@php
    $cfg = config('traziva.idc');
    $colores = ['alto' => 'verde', 'medio' => 'amarillo', 'bajo' => 'rojo', 'incompleto' => 'gris'];
    $barras = ['alto' => 'bg-green-600', 'medio' => 'bg-amber-500', 'bajo' => 'bg-agro-clay', 'incompleto' => 'bg-gray-300'];
    $variables = ['rendimiento' => 'Rendimiento', 'calidad' => 'Calidad', 'aprovechamiento' => 'Aprovechamiento', 'constancia' => 'Constancia'];
@endphp
@section('contenido')
<x-page-header titulo="Índice de Desempeño del Cultivo" subtitulo="Comparación del desempeño de las plantaciones. Indicador referencial calculado por reglas, no un diagnóstico agronómico.">
    @can('recalcular-idc')
        <form method="POST" action="{{ route('idc.recalcular') }}" onsubmit="return confirm('¿Recalcular el IDC de todas las plantaciones activas?')">
            @csrf
            <button class="btn-secondary"><x-icon name="medidor" class="w-4 h-4" /> Recalcular todos</button>
        </form>
    @endcan
</x-page-header>

<x-alert tipo="aviso" class="mb-6">
    <strong>Referencial · fórmula en borrador ({{ $cfg['version'] }}).</strong>
    Pesos y rangos pendientes de validar con el tutor y Amanecer. Las categorías de calidad y las metas por cama son provisionales.
</x-alert>

{{-- Resumen por categoría (clic = filtrar) --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach(\App\Http\Controllers\IdcController::CATEGORIAS as $clave => $texto)
        @php $activo = request('categoria') === $clave; @endphp
        <a href="{{ request()->fullUrlWithQuery(['categoria' => $activo ? null : $clave]) }}"
           class="card !p-5 block transition hover:shadow-md {{ $activo ? 'ring-2 ring-agro-moss' : '' }}">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500">Desempeño {{ mb_strtolower($texto) }}</p>
                <span class="w-2.5 h-2.5 rounded-full {{ $barras[$clave] }}"></span>
            </div>
            <p class="text-3xl font-semibold tabular-nums mt-1">{{ $resumen[$clave] }}</p>
            <p class="text-xs text-gray-500 mt-1">
                @switch($clave)
                    @case('alto') {{ $cfg['rangos']['alto'] }} puntos o más @break
                    @case('medio') de {{ $cfg['rangos']['medio'] }} a {{ $cfg['rangos']['alto'] - 1 }} puntos @break
                    @case('bajo') menos de {{ $cfg['rangos']['medio'] }} puntos @break
                    @default faltan datos mínimos
                @endswitch
            </p>
        </a>
    @endforeach
</div>

<form method="GET" class="card !p-4 mb-6 grid gap-3 md:grid-cols-[1fr_1fr_1fr_auto]">
    <select name="cultivo_id" class="input-modern" onchange="this.form.submit()" aria-label="Cultivo">
        <option value="">Todos los cultivos</option>
        @foreach($cultivos as $id => $nombre)<option value="{{ $id }}" @selected(request('cultivo_id') == $id)>{{ $nombre }}</option>@endforeach
    </select>
    <select name="categoria" class="input-modern" onchange="this.form.submit()" aria-label="Categoría">
        <option value="">Todas las categorías</option>
        @foreach(\App\Http\Controllers\IdcController::CATEGORIAS as $v => $t)<option value="{{ $v }}" @selected(request('categoria') === $v)>{{ $t }}</option>@endforeach
    </select>
    <select name="datos" class="input-modern" onchange="this.form.submit()" aria-label="Origen de datos">
        <option value="">Reales y de prueba</option>
        <option value="reales" @selected(request('datos') === 'reales')>Solo datos reales</option>
        <option value="prueba" @selected(request('datos') === 'prueba')>Solo datos de prueba</option>
    </select>
    <a href="{{ route('idc.index') }}" class="btn-secondary">Limpiar</a>
</form>

@if($plantaciones->isEmpty())
    <x-card><x-empty-state titulo="Ninguna plantación coincide con los filtros" icono="medidor" /></x-card>
@else
<x-table>
    <x-slot:head>
        <th>Plantación</th><th>IDC</th>
        @foreach($variables as $v => $t)<th class="hidden lg:table-cell">{{ $t }} <span class="normal-case text-gray-400">{{ (int) round($cfg['pesos'][$v] * 100) }}%</span></th>@endforeach
        <th>Datos faltantes / cálculo</th>
    </x-slot:head>
    @foreach($plantaciones as $p)
        @php
            $idc = $p->ultimoIdc;
            $valores = $idc?->variables->pluck('valor_normalizado', 'variable') ?? collect();
        @endphp
        <tr class="hover:bg-agro-cream/60 align-top">
            <td>
                <a href="{{ route('plantaciones.show', $p) }}#idc" class="font-mono text-xs text-agro-moss hover:underline">{{ $p->codigo }}</a>
                @if($p->es_prueba)<x-badge color="azul" class="ml-1">Prueba</x-badge>@endif
                <p class="font-medium">{{ $p->cultivo->nombre }}</p>
                <p class="text-xs text-gray-500">{{ $p->nave ? 'Nave '.$p->nave->numero : 'Sin nave' }} · {{ \App\Models\Plantacion::ESTADOS[$p->estado] }}</p>
            </td>
            <td class="whitespace-nowrap">
                <div class="flex items-center gap-2">
                    <span class="text-xl font-semibold tabular-nums">{{ $idc?->puntaje !== null ? number_format($idc->puntaje, 0) : '—' }}</span>
                    <x-badge :color="$colores[$p->categoria_idc]">{{ \App\Http\Controllers\IdcController::CATEGORIAS[$p->categoria_idc] }}</x-badge>
                </div>
            </td>
            @foreach($variables as $v => $t)
                <td class="hidden lg:table-cell w-32">
                    @if($valores->has($v) && $valores[$v] !== null)
                        <div class="flex items-center gap-2">
                            <div class="h-1.5 flex-1 rounded-full bg-gray-100 overflow-hidden" role="img" aria-label="{{ $t }} {{ (int) $valores[$v] }} de 100">
                                <div class="h-full rounded-full {{ $barras[$p->categoria_idc] }}" style="width: {{ min(100, max(0, (float) $valores[$v])) }}%"></div>
                            </div>
                            <span class="text-xs tabular-nums w-7 text-right">{{ (int) round($valores[$v]) }}</span>
                        </div>
                    @else
                        <span class="text-gray-300">—</span>
                    @endif
                </td>
            @endforeach
            <td class="text-sm">
                @if(! $idc)
                    <p class="text-gray-600">Sin cálculo: faltan cosechas registradas.</p>
                @elseif($idc->datos_faltantes)
                    <ul class="text-gray-600 list-disc pl-4">@foreach($idc->datos_faltantes as $falta)<li>{{ $falta }}</li>@endforeach</ul>
                @else
                    <p class="text-gray-500">Completo</p>
                @endif
                @if($idc)<p class="text-xs text-gray-400 mt-1">{{ $idc->calculado_en->format('d/m/Y H:i') }} · {{ $idc->version_formula }}</p>@endif
            </td>
        </tr>
    @endforeach
</x-table>
@endif

{{-- Cómo se calcula --}}
<x-card titulo="¿Cómo se calcula el IDC?" class="mt-6">
    <div class="grid gap-6 md:grid-cols-2 text-sm">
        <div>
            <p class="font-mono text-xs bg-agro-cream rounded-lg p-3 mb-3">
                IDC = @foreach($variables as $v => $t){{ mb_strtolower($t) }} × {{ number_format($cfg['pesos'][$v], 2) }}@if(! $loop->last) + @endif @endforeach
            </p>
            <ul class="space-y-1.5 text-gray-700">
                <li><strong>Rendimiento:</strong> kg cosechados frente a la meta por cama del período (desde la primera cosecha hasta hoy).</li>
                <li><strong>Calidad:</strong> promedio de la categoría de calidad de las cosechas, ponderado por kg.</li>
                <li><strong>Aprovechamiento:</strong> proporción de lo cosechado que no se perdió (1 − merma).</li>
                <li><strong>Constancia:</strong> días con registros (labores, aplicaciones, eventos) frente a uno cada {{ $cfg['frecuencia_registro_dias'] }} días.</li>
                <li class="text-gray-500"><strong>Estimación de cosecha:</strong> se muestra en el detalle pero pesa 0 %: los retrasos suelen deberse al clima o a imprevistos.</li>
            </ul>
        </div>
        <div class="space-y-3 text-gray-700">
            <p><strong>Datos mínimos:</strong> meta por cama y su período, número de camas, al menos una cosecha y la calidad de todas las cosechas. Si falta alguno, el IDC es «Incompleto» y se indica qué falta.</p>
            <p><strong>Categorías:</strong> alto ≥ {{ $cfg['rangos']['alto'] }}, medio ≥ {{ $cfg['rangos']['medio'] }}, bajo &lt; {{ $cfg['rangos']['medio'] }}.</p>
            <p><strong>Cuándo se calcula:</strong> automáticamente al registrar o editar una cosecha, con «Recalcular IDC» en el detalle o con «Recalcular todos». Cada cálculo se guarda con sus variables y la versión de la fórmula.</p>
        </div>
    </div>
</x-card>
@endsection
