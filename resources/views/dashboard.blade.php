@extends('layouts.app')
@section('titulo', 'Dashboard')
@section('contenido')
<x-page-header titulo="Dashboard" subtitulo="Resumen de la producción registrada en Traziva." />

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    @foreach($totales as $titulo => $n)
        <div class="card !p-5">
            <p class="text-sm text-gray-500">{{ $titulo }}</p>
            <p class="text-3xl font-semibold text-agro-forest mt-1 tabular-nums">{{ $n }}</p>
        </div>
    @endforeach
</div>

<x-alert tipo="info" class="mb-6">Los gráficos de producción, estados, desempeño y eventos fitosanitarios se incorporan en el Sprint 7.</x-alert>

<h2 class="mb-3">Plantaciones</h2>
@include('plantaciones._tabla', ['plantaciones' => $plantaciones])
@endsection
