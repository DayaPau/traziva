@extends('layouts.app')
@section('titulo', 'Editar labor')
@section('contenido')
@php $p = $labor->plantacion; @endphp
<x-page-header titulo="Editar labor cultural" :subtitulo="$p->codigo.' · '.$p->cultivo->nombre.($p->nave ? ' · Nave '.$p->nave->numero : '')">
    <a href="{{ route('plantaciones.show', $p) }}#labores" class="btn-secondary">Volver</a>
</x-page-header>

<x-card class="max-w-2xl">
    <form method="POST" action="{{ route('labores.update', $labor) }}" class="space-y-5">
        @csrf @method('PUT')
        @include('labores._campos')
        <div>
            <label for="observaciones" class="label">Observaciones</label>
            <textarea id="observaciones" name="observaciones" rows="3" maxlength="2000" class="input-modern">{{ old('observaciones', $labor->observaciones) }}</textarea>
            @error('observaciones')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <p class="text-xs text-gray-500">El cambio queda registrado en el historial de la plantación.</p>
        <div class="flex gap-3">
            <button class="btn-primary">Guardar cambios</button>
            <a href="{{ route('plantaciones.show', $p) }}#labores" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</x-card>
@endsection
