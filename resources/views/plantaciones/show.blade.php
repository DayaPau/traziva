@extends('layouts.app')
@section('titulo', $p->codigo)
@php
    $P = \App\Models\Plantacion::class;
    $kg = fn ($n) => $n === null ? '—' : number_format((float) $n, 2, ',', '.').' kg';
    $fecha = fn ($f) => $f?->format('d/m/Y') ?? '—';
    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
    $pestanas = [
        'resumen' => 'Resumen',
        'labores' => 'Labores ('.$labores->count().')',
        'aplicaciones' => 'Aplicaciones ('.$aplicaciones->count().')',
        'eventos' => 'Eventos fitosanitarios ('.$eventos->count().')',
        'cosechas' => 'Cosechas ('.$cosechas->count().')',
        'idc' => 'IDC',
        'historial' => 'Historial',
    ];
@endphp
@section('contenido')
<x-page-header :titulo="$p->cultivo->nombre.($p->nave ? ' · Nave '.$p->nave->numero : '')" :subtitulo="'Código '.$p->codigo.' · Siembra '.$p->siembra_texto">
    @can('update', $p)
        <a href="{{ route('plantaciones.edit', $p) }}" class="btn-primary"><x-icon name="lapiz" class="w-4 h-4" /> Editar</a>
    @endcan
    <a href="{{ route('plantaciones.index') }}" class="btn-secondary">Volver al listado</a>
</x-page-header>

<div class="flex flex-wrap items-center gap-2 mb-6">
    @include('plantaciones._insignias')
    @if($p->es_prueba)<x-badge color="azul">Datos de prueba</x-badge>@endif
</div>

{{-- Pestañas: la activa se guarda en la URL (#cosechas) para volver a ella tras registrar algo. --}}
<div x-data="{ tab: (location.hash.slice(1) in @js($pestanas)) ? location.hash.slice(1) : 'resumen' }"
     x-init="$watch('tab', t => history.replaceState(null, '', '#' + t))">
    <nav class="flex gap-1 overflow-x-auto border-b border-gray-200 mb-6" role="tablist">
        @foreach($pestanas as $clave => $texto)
            <button type="button" role="tab" @click="tab = '{{ $clave }}'" :aria-selected="tab === '{{ $clave }}'"
                    class="whitespace-nowrap px-4 py-2.5 text-sm border-b-2 -mb-px transition-colors"
                    :class="tab === '{{ $clave }}' ? 'border-agro-moss text-agro-moss font-semibold' : 'border-transparent text-gray-500 hover:text-gray-800'">
                {{ $texto }}
            </button>
        @endforeach
    </nav>

    {{-- RESUMEN --}}
    <div x-show="tab === 'resumen'" role="tabpanel" class="grid gap-6 lg:grid-cols-3">
        <x-card titulo="Datos de la plantación" class="lg:col-span-2">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
                @foreach([
                    'Cultivo' => $p->cultivo->nombre,
                    'Variedad' => $p->variedad ?? '—',
                    'Nave' => $p->nave ? 'Nave '.$p->nave->numero : '—',
                    'Fecha de siembra' => $p->siembra_texto.($p->fecha_siembra_precision !== 'dia' ? ' ('.mb_strtolower($P::PRECISIONES[$p->fecha_siembra_precision]).')' : ''),
                    'Camas' => $p->num_camas ?? '—',
                    'Plantas' => $p->num_plantas ?? '—',
                    'Inicio estimado de cosecha' => $fecha($p->fecha_inicio_cosecha_estimada),
                    'Inicio real de cosecha' => $fecha($p->fecha_inicio_cosecha_real),
                    'Salida estimada' => $fecha($p->fecha_salida_estimada),
                    'Meta productiva' => $p->meta_kg_por_cama ? $num($p->meta_kg_por_cama).' kg/cama · '.mb_strtolower($P::PERIODOS_META[$p->meta_periodo] ?? '') : '—',
                    'Responsable' => $p->responsable?->name ?? 'Sin asignar',
                    'Registrada por' => $p->creador?->name ?? 'Importación inicial',
                ] as $etiqueta => $valor)
                    <div><dt class="text-gray-500">{{ $etiqueta }}</dt><dd class="font-medium mt-0.5">{{ $valor }}</dd></div>
                @endforeach
            </dl>
            @if($p->observaciones)
                <div class="mt-6 rounded-xl bg-agro-cream p-4 text-sm whitespace-pre-line"><p class="font-medium mb-1">Observaciones</p>{{ $p->observaciones }}</div>
            @endif
        </x-card>
        <div class="space-y-6">
            <x-card titulo="Producción acumulada">
                <p class="text-3xl font-semibold text-agro-forest tabular-nums">{{ $kg($cosechas->sum('cantidad_aprovechable_kg')) }}</p>
                <p class="text-sm text-gray-500">aprovechables de {{ $kg($cosechas->sum('cantidad_kg')) }} cosechados en {{ $cosechas->count() }} cortes</p>
            </x-card>
            <x-card titulo="Registros">
                <ul class="text-sm space-y-1.5">
                    <li class="flex justify-between"><span>Labores culturales</span><span class="font-medium">{{ $labores->count() }}</span></li>
                    <li class="flex justify-between"><span>Aplicaciones</span><span class="font-medium">{{ $aplicaciones->count() }}</span></li>
                    <li class="flex justify-between"><span>Eventos fitosanitarios</span><span class="font-medium">{{ $eventos->count() }}</span></li>
                    <li class="flex justify-between"><span>Cosechas</span><span class="font-medium">{{ $cosechas->count() }}</span></li>
                </ul>
            </x-card>
            @can('delete', $p)
                @unless($p->tieneRegistros())
                    <form method="POST" action="{{ route('plantaciones.destroy', $p) }}" onsubmit="return confirm('¿Eliminar la plantación {{ $p->codigo }}? Solo es posible porque aún no tiene registros.')">
                        @csrf @method('DELETE')
                        <button class="btn-secondary w-full !text-red-700"><x-icon name="basura" class="w-4 h-4" /> Eliminar (sin registros)</button>
                    </form>
                @endunless
            @endcan
        </div>
    </div>

    {{-- LABORES --}}
    <div x-show="tab === 'labores'" x-cloak role="tabpanel">
        @if($labores->isEmpty())
            <x-card><x-empty-state titulo="Sin labores culturales registradas" icono="portapapeles">El registro de labores se habilita en la Fase 3.</x-empty-state></x-card>
        @else
            <x-table>
                <x-slot:head><th>Fecha</th><th>Labor</th><th>Descripción</th><th>Observaciones</th><th>Registró</th></x-slot:head>
                @foreach($labores as $l)
                    <tr><td class="whitespace-nowrap">{{ $fecha($l->fecha) }}</td><td class="font-medium">{{ $l->tipo->nombre }}</td>
                        <td>{{ $l->descripcion ?? '—' }}</td><td class="text-gray-600">{{ $l->observaciones ?? '—' }}</td><td>{{ $l->registrador?->name ?? 'Importación' }}</td></tr>
                @endforeach
            </x-table>
        @endif
    </div>

    {{-- APLICACIONES --}}
    <div x-show="tab === 'aplicaciones'" x-cloak role="tabpanel">
        @if($aplicaciones->isEmpty())
            <x-card><x-empty-state titulo="Sin aplicaciones registradas">El registro de aplicaciones se habilita en la Fase 3.</x-empty-state></x-card>
        @else
            <x-table>
                <x-slot:head><th>Fecha</th><th>Objetivo</th><th>Plaga / enfermedad</th><th>Productos y dosis</th></x-slot:head>
                @foreach($aplicaciones as $a)
                    <tr>
                        <td class="whitespace-nowrap align-top">{{ $fecha($a->fecha_aplicacion) }}</td>
                        <td class="align-top">{{ ['fertilizacion' => 'Fertilización', 'control_fitosanitario' => 'Control fitosanitario'][$a->objetivo] ?? 'Otro' }}</td>
                        <td class="align-top">{{ $a->plaga?->nombre ?? '—' }}</td>
                        <td>
                            <ul class="space-y-0.5">
                                @foreach($a->detalles as $d)
                                    <li><span class="font-medium">{{ $d->insumo->nombre }}</span>: {{ $num($d->dosis_cantidad) }} {{ $d->dosis_unidad }}
                                        {{ $d->base_tipo === 'por_planta' ? 'por planta' : 'en '.$num($d->base_valor).' L de mezcla' }}</li>
                                @endforeach
                            </ul>
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </div>

    {{-- EVENTOS --}}
    <div x-show="tab === 'eventos'" x-cloak role="tabpanel">
        @if($eventos->isEmpty())
            <x-card><x-empty-state titulo="Sin eventos fitosanitarios registrados" icono="auditoria">El registro de eventos se habilita en la Fase 3.</x-empty-state></x-card>
        @else
            <x-table>
                <x-slot:head><th>Fecha</th><th>Plaga / enfermedad</th><th>Nivel</th><th>Descripción</th></x-slot:head>
                @foreach($eventos as $e)
                    <tr><td class="whitespace-nowrap">{{ $fecha($e->fecha_evento) }}</td><td class="font-medium">{{ $e->plaga->nombre }}</td>
                        <td>@if($e->nivel_afectacion)<x-badge :color="['bajo' => 'verde', 'medio' => 'amarillo', 'alto' => 'rojo'][$e->nivel_afectacion]">{{ ucfirst($e->nivel_afectacion) }}</x-badge>@else — @endif</td>
                        <td>{{ $e->descripcion ?? '—' }}</td></tr>
                @endforeach
            </x-table>
        @endif
    </div>

    {{-- COSECHAS --}}
    <div x-show="tab === 'cosechas'" x-cloak role="tabpanel" class="space-y-6">
        @can('registrar', $p)
            <x-card titulo="Registrar cosecha" subtitulo="La cantidad aprovechable se calcula sola (cantidad − merma). Registro completo de postcosecha en la Fase 4.">
                <form method="POST" action="{{ route('cosechas.store', $p) }}" class="grid gap-4 md:grid-cols-5 items-end">
                    @csrf
                    <x-input name="fecha_cosecha" label="Fecha" type="date" required max="{{ today()->toDateString() }}" />
                    <x-input name="cantidad_kg" label="Cantidad (kg)" type="number" step="0.001" min="0.001" required />
                    <x-input name="merma_kg" label="Merma (kg)" type="number" step="0.001" min="0" />
                    <x-select name="categoria_calidad_id" label="Calidad (provisional)" :opciones="$calidades" vacio="Selecciona…" />
                    <button class="btn-primary">Registrar</button>
                </form>
            </x-card>
        @endcan
        @if($cosechas->isEmpty())
            <x-card><x-empty-state titulo="Sin cosechas registradas" icono="cosecha" /></x-card>
        @else
            <x-table>
                <x-slot:head><th>Fecha</th><th class="text-right">Cantidad</th><th class="text-right">Merma</th><th class="text-right">Aprovechable</th><th>Calidad</th><th>Observaciones</th></x-slot:head>
                @foreach($cosechas as $c)
                    <tr><td class="whitespace-nowrap">{{ $fecha($c->fecha_cosecha) }}</td><td class="text-right tabular-nums">{{ $kg($c->cantidad_kg) }}</td>
                        <td class="text-right tabular-nums">{{ $kg($c->merma_kg) }}</td><td class="text-right tabular-nums font-medium">{{ $kg($c->cantidad_aprovechable_kg) }}</td>
                        <td>{{ $c->calidad?->nombre ?? '—' }}</td><td class="text-gray-600">{{ $c->observaciones ?? '—' }}</td></tr>
                @endforeach
            </x-table>
        @endif
    </div>

    {{-- IDC --}}
    <div x-show="tab === 'idc'" x-cloak role="tabpanel" class="space-y-6">
        <x-alert tipo="aviso"><strong>Referencial.</strong> El Índice de Desempeño del Cultivo usa una fórmula en borrador ({{ config('traziva.idc.version') }}) pendiente de validar con el tutor y Amanecer. No es un diagnóstico agronómico.</x-alert>
        <x-card titulo="Último cálculo">
            <x-slot:acciones>
                @can('registrar', $p)
                    <form method="POST" action="{{ route('plantaciones.idc', $p) }}">@csrf <button class="btn-secondary">Recalcular IDC</button></form>
                @endcan
            </x-slot:acciones>
            @if(! $p->ultimoIdc)
                <x-empty-state titulo="Aún no se ha calculado el IDC" icono="medidor" />
            @else
                @php $u = $p->ultimoIdc; @endphp
                <div class="flex flex-wrap items-baseline gap-3 mb-4">
                    <span class="text-3xl font-semibold tabular-nums">{{ $u->puntaje !== null ? number_format($u->puntaje, 1, ',', '') : '—' }}</span>
                    @include('plantaciones._idc', ['idc' => $u])
                    <span class="text-sm text-gray-500">Categoría {{ $u->categoria }} · {{ $u->calculado_en->format('d/m/Y H:i') }}</span>
                </div>
                @if($u->datos_faltantes)
                    <x-alert tipo="info" class="mb-4">Faltan datos para calcularlo: {{ implode(', ', $u->datos_faltantes) }}.</x-alert>
                @endif
                @if($u->variables->isNotEmpty())
                    <x-table>
                        <x-slot:head><th>Variable</th><th class="text-right">Valor crudo</th><th class="text-right">Normalizado (0–100)</th><th class="text-right">Peso</th></x-slot:head>
                        @foreach($u->variables as $v)
                            <tr><td>{{ ucfirst(str_replace('_', ' ', $v->variable)) }}</td><td class="text-right tabular-nums">{{ $v->valor_crudo ?? '—' }}</td>
                                <td class="text-right tabular-nums">{{ $v->valor_normalizado ?? '—' }}</td><td class="text-right tabular-nums">{{ number_format($v->peso * 100, 0) }} %</td></tr>
                        @endforeach
                    </x-table>
                @endif
            @endif
        </x-card>
        @if($calculosIdc->count() > 1)
            <x-card titulo="Historial de cálculos">
                <ul class="text-sm divide-y divide-gray-100">
                    @foreach($calculosIdc as $c)
                        <li class="py-2 flex flex-wrap gap-3 justify-between"><span>{{ $c->calculado_en->format('d/m/Y H:i') }} · {{ $c->tipo }}</span>
                            <span>{{ $c->puntaje !== null ? number_format($c->puntaje, 1, ',', '') : 'Incompleto' }} · {{ $c->version_formula }}</span></li>
                    @endforeach
                </ul>
            </x-card>
        @endif
    </div>

    {{-- HISTORIAL --}}
    <div x-show="tab === 'historial'" x-cloak role="tabpanel">
        @if($historial->isEmpty())
            <x-card><x-empty-state titulo="Sin cambios auditados" icono="auditoria">Los registros importados desde Excel no generan historial individual.</x-empty-state></x-card>
        @else
            @php $nombres = ['plantaciones' => 'Plantación', 'labores_culturales' => 'Labor', 'aplicaciones' => 'Aplicación', 'eventos_fitosanitarios' => 'Evento', 'cosechas' => 'Cosecha']; @endphp
            <x-table>
                <x-slot:head><th>Fecha y hora</th><th>Usuario</th><th>Acción</th><th>Registro</th><th>Campos modificados</th></x-slot:head>
                @foreach($historial as $h)
                    <tr>
                        <td class="whitespace-nowrap">{{ $h->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $h->user?->name ?? 'Sistema' }}</td>
                        <td><x-badge :color="['crear' => 'verde', 'modificar' => 'amarillo', 'eliminar' => 'rojo'][$h->accion] ?? 'gris'">{{ ucfirst($h->accion) }}</x-badge></td>
                        <td>{{ $nombres[$h->entidad] ?? $h->entidad }} #{{ $h->entidad_id }}</td>
                        <td class="text-xs text-gray-600">
                            @if($h->accion === 'modificar')
                                @foreach(collect($h->valores_despues)->except(['updated_at']) as $campo => $nuevo)
                                    <div><span class="font-medium">{{ $campo }}</span>: {{ $h->valores_antes[$campo] ?? '∅' }} → {{ $nuevo ?? '∅' }}</div>
                                @endforeach
                            @else — @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </div>
</div>
@endsection
