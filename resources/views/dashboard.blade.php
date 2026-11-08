@extends('layouts.app')
@section('contenido')
<h1 class="mb-4">Panel general</h1>
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    @foreach($totales as $titulo => $n)
        <div class="bg-white rounded-lg shadow p-4"><div class="text-3xl font-bold text-agro-primary">{{ $n }}</div><div class="text-sm">{{ $titulo }}</div></div>
    @endforeach
</div>
@include('plantaciones._tabla', ['plantaciones' => $plantaciones])
@endsection
