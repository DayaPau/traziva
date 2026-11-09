@extends('layouts.app')
@php $editando = $registro->exists; @endphp
@section('titulo', ($editando ? 'Editar ' : 'Registrar ').$def['singular'])
@section('contenido')
<x-page-header :titulo="($editando ? 'Editar ' : 'Registrar ').$def['singular']" :subtitulo="$def['titulo']">
    <a href="{{ route('catalogos.index', $catalogo) }}" class="btn-secondary">Volver</a>
</x-page-header>

@isset($def['provisional'])
    <x-alert tipo="aviso" class="mb-6"><strong>Provisional.</strong> {{ $def['provisional'] }}</x-alert>
@endisset

<x-card class="max-w-2xl">
    <form method="POST" action="{{ $editando ? route('catalogos.update', [$catalogo, $registro->id]) : route('catalogos.store', $catalogo) }}" class="space-y-5">
        @csrf
        @if($editando) @method('PUT') @endif

        @foreach($def['campos'] as $campo => $c)
            @switch($c['tipo'])
                @case('select')
                    <x-select :name="$campo" :label="$c['label']" :opciones="$c['opciones']" :valor="$registro->$campo"
                              :vacio="in_array('required', $c['reglas']) ? null : 'Sin definir'" />
                    @break
                @case('relacion')
                    @php
                        $elegidos = collect(old($campo, $registro->exists ? $registro->$campo->pluck('id')->all() : []))->map(fn ($v) => (int) $v);
                        $opciones = $c['modelo']::where('activo', true)->orWhereIn('id', $elegidos)->orderBy($c['texto'])->get();
                    @endphp
                    <fieldset>
                        <legend class="label">{{ $c['label'] }}</legend>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            @foreach($opciones as $o)
                                <label class="flex items-center gap-2 text-sm rounded-lg border border-gray-200 px-3 py-2 hover:bg-agro-cream">
                                    <input type="checkbox" name="{{ $campo }}[]" value="{{ $o->id }}" @checked($elegidos->contains($o->id))
                                           class="rounded border-gray-300 text-agro-moss focus:ring-agro-moss">
                                    {{ $o->{$c['texto']} }}
                                </label>
                            @endforeach
                        </div>
                        @error($campo)<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </fieldset>
                    @break
                @default
                    <x-input :name="$campo" :label="$c['label']" :type="$c['tipo']" :value="$registro->$campo" :ayuda="$c['ayuda'] ?? null"
                             :required="in_array('required', $c['reglas'])" />
            @endswitch
        @endforeach

        <div class="flex gap-3 pt-2">
            <button class="btn-primary">{{ $editando ? 'Guardar cambios' : 'Registrar' }}</button>
            <a href="{{ route('catalogos.index', $catalogo) }}" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</x-card>
@endsection
