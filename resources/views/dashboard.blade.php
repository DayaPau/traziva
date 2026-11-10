@extends('layouts.app')
@section('titulo', 'Dashboard')
@section('contenido')
<x-page-header titulo="Dashboard" subtitulo="Resumen de la producción real registrada en Traziva (no incluye datos de prueba)." />

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    @foreach($totales as $titulo => $n)
        <div class="card !p-5">
            <p class="text-sm text-gray-500">{{ $titulo }}</p>
            <p class="text-3xl font-semibold text-agro-forest mt-1 tabular-nums">{{ $n }}</p>
        </div>
    @endforeach
</div>

@if($plantacionesPrueba)
    <x-alert tipo="info" class="mb-6">
        Hay {{ $plantacionesPrueba }} plantaciones de <strong>prueba</strong> (códigos TEST-…, datos sintéticos para demostrar el IDC).
        No se suman en los totales; en la tabla aparecen al final con la insignia «Prueba».
    </x-alert>
@endif

<h2 class="mb-3">Plantaciones</h2>
@include('plantaciones._tabla', ['plantaciones' => $plantaciones])
@endsection
