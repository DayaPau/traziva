@extends('layouts.app')
@section('titulo', 'Editar cosecha')
@section('contenido')
@php $p = $cosecha->plantacion; @endphp
<x-page-header titulo="Editar cosecha" :subtitulo="$p->codigo.' · '.$p->cultivo->nombre.($p->nave ? ' · Nave '.$p->nave->numero : '')">
    <a href="{{ route('plantaciones.show', $p) }}#cosechas" class="btn-secondary">Volver</a>
</x-page-header>

<x-card class="max-w-4xl">
    <form method="POST" action="{{ route('cosechas.update', $cosecha) }}" class="space-y-5">
        @csrf @method('PUT')
        @include('cosechas._campos')
        <p class="text-xs text-gray-500">Al guardar se recalcula el IDC parcial y el cambio queda en el historial de la plantación.</p>
        <div class="flex gap-3">
            <button class="btn-primary">Guardar cambios</button>
            <a href="{{ route('plantaciones.show', $p) }}#cosechas" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</x-card>
@endsection
