@extends('layouts.app')
@section('titulo', $p->codigo)
@section('contenido')
<h1>{{ $p->codigo }} · {{ $p->cultivo->nombre }}</h1>
<p class="mb-4 text-sm">Nave {{ $p->nave?->numero ?? '—' }} · Siembra {{ $p->siembra_texto }} · Camas {{ $p->num_camas ?? '—' }} · Plantas {{ $p->num_plantas ?? '—' }} · Estado {{ $p->estado }}</p>
@if($p->observaciones)<div class="mb-4 p-3 bg-yellow-50 rounded text-sm whitespace-pre-line">{{ $p->observaciones }}</div>@endif

<section class="card mb-6">
    <h3>Índice de Desempeño de Cultivo (referencial · fórmula en borrador)</h3>
    @if($p->ultimoIdc)
        <p>{{ $p->ultimoIdc->puntaje !== null ? $p->ultimoIdc->puntaje.' / 100 — ' : '' }}<strong>{{ $p->ultimoIdc->categoria }}</strong> (v. {{ $p->ultimoIdc->version_formula }})</p>
        @if($p->ultimoIdc->datos_faltantes)<p class="text-sm">Datos faltantes: {{ implode(', ', $p->ultimoIdc->datos_faltantes) }}</p>@endif
    @else <p class="text-sm">Aún no calculado.</p> @endif
    @can('registrar', $p)
        <form method="POST" action="{{ route('plantaciones.idc', $p) }}" class="mt-2">@csrf <button class="btn-primary">Recalcular IDC</button></form>
    @endcan
</section>

<section class="card mb-6">
    <h3>Labores culturales ({{ $labores->count() }})</h3>
    <ul class="text-sm columns-2">@foreach($labores as $l)<li>{{ $l->fecha->format('d/m/Y') }} — {{ $l->tipo->nombre }}</li>@endforeach</ul>
</section>

<section class="card mb-6 overflow-x-auto">
    <h3>Aplicaciones ({{ $aplicaciones->count() }})</h3>
    <table class="w-full text-sm"><thead><tr class="text-left"><th>Fecha</th><th>Objetivo</th><th>Plaga/enfermedad</th><th>Insumos y dosis</th></tr></thead><tbody>
    @foreach($aplicaciones as $a)
        <tr class="border-t"><td>{{ $a->fecha_aplicacion->format('d/m/Y') }}</td><td>{{ str_replace('_',' ',$a->objetivo) }}</td><td>{{ $a->plaga?->nombre ?? '—' }}</td>
        <td>@foreach($a->detalles as $d){{ $d->insumo->nombre }}: {{ rtrim(rtrim(number_format($d->dosis_cantidad,2,'.',''),'0'),'.') }} {{ $d->dosis_unidad }} {{ $d->base_tipo === 'por_planta' ? 'por planta' : 'por '.rtrim(rtrim(number_format($d->base_valor,2,'.',''),'0'),'.').' L de mezcla' }}@if(!$loop->last); @endif @endforeach</td></tr>
    @endforeach
    </tbody></table>
</section>

<section class="card mb-6">
    <h3>Cosechas ({{ $cosechas->count() }})</h3>
    <ul class="text-sm">@foreach($cosechas as $c)<li>{{ $c->fecha_cosecha->format('d/m/Y') }} — {{ $c->cantidad_kg }} kg (merma {{ $c->merma_kg }}, aprovechable {{ $c->cantidad_aprovechable_kg }}) {{ $c->calidad?->nombre }}</li>@endforeach</ul>
    @can('registrar', $p)
    <form method="POST" action="{{ route('cosechas.store', $p) }}" class="mt-3 grid md:grid-cols-5 gap-2 text-sm">@csrf
        <input type="date" name="fecha_cosecha" required class="border rounded px-2 py-1" max="{{ now()->toDateString() }}">
        <input type="number" step="0.001" min="0.001" name="cantidad_kg" placeholder="Cantidad kg" required class="border rounded px-2 py-1">
        <input type="number" step="0.001" min="0" name="merma_kg" placeholder="Merma kg" class="border rounded px-2 py-1">
        <select name="categoria_calidad_id" class="border rounded px-2 py-1"><option value="">Calidad…</option>@foreach($calidades as $c)<option value="{{ $c->id }}">{{ $c->nombre }}</option>@endforeach</select>
        <button class="btn-primary">Registrar</button>
    </form>@endcan
</section>
@endsection
