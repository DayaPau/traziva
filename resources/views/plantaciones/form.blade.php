@extends('layouts.app')
@php
    $P = \App\Models\Plantacion::class; // listas de opciones: $P::ESTADOS, $P::ETAPAS…
    $editando = $p->exists;
    $fecha = fn (string $campo) => old($campo, $p->{$campo}?->format('Y-m-d'));
@endphp
@section('titulo', $editando ? "Editar {$p->codigo}" : 'Registrar plantación')
@section('contenido')
<x-page-header :titulo="$editando ? 'Editar plantación' : 'Registrar plantación'"
               :subtitulo="$editando ? $p->codigo.' · '.$p->cultivo->nombre : 'Una plantación es un cultivo sembrado en una nave en una fecha determinada.'">
    <a href="{{ $editando ? route('plantaciones.show', $p) : route('plantaciones.index') }}" class="btn-secondary">Cancelar</a>
</x-page-header>

@if($cultivos->isEmpty() || $naves->isEmpty())
    <x-alert tipo="aviso" class="mb-6">
        Antes de registrar una plantación necesitas al menos un cultivo y una nave activos.
        <a class="underline font-medium" href="{{ route('catalogos.index', $cultivos->isEmpty() ? 'cultivos' : 'naves') }}">Ir a catálogos</a>
    </x-alert>
@endif

{{-- La sugerencia del código se actualiza al cambiar cultivo, nave o fecha, salvo que el usuario lo haya escrito a mano. --}}
<form method="POST" action="{{ $editando ? route('plantaciones.update', $p) : route('plantaciones.store') }}" class="space-y-6"
      x-data="{
          codigo: @js(old('codigo', $p->codigo)),
          manual: @js($editando || filled(old('codigo'))),
          async sugerir() {
              if (this.manual) return;
              const f = new FormData(this.$el);
              if (! f.get('cultivo_id')) return;
              const params = new URLSearchParams({ cultivo_id: f.get('cultivo_id'), nave_id: f.get('nave_id') ?? '', fecha_siembra: f.get('fecha_siembra') ?? '', fecha_siembra_precision: f.get('fecha_siembra_precision') ?? 'dia' });
              const r = await fetch(@js(route('plantaciones.sugerir-codigo')) + '?' + params, { headers: { Accept: 'application/json' } });
              if (r.ok) this.codigo = (await r.json()).codigo;
          }
      }" @change="if (['cultivo_id','nave_id','fecha_siembra','fecha_siembra_precision'].includes($event.target.name)) sugerir()">
    @csrf
    @if($editando) @method('PUT') @endif

    <x-card titulo="Identificación">
        <div class="grid gap-5 md:grid-cols-2">
            <x-select name="cultivo_id" label="Cultivo *" :opciones="$cultivos" :valor="$p->cultivo_id" vacio="Selecciona…" required />
            <x-select name="nave_id" label="Nave *" :opciones="$naves" :valor="$p->nave_id" vacio="Selecciona…" :required="! $editando || (bool) $p->nave_id" />
            <x-input name="variedad" label="Variedad" :value="$p->variedad" maxlength="80" placeholder="Ej.: Albión" />
            <div>
                <label for="codigo" class="label">Código</label>
                <input id="codigo" name="codigo" x-model="codigo" @input="manual = true" maxlength="40" placeholder="Se genera automáticamente"
                       class="input-modern font-mono uppercase @error('codigo') border-red-400 @enderror">
                <p class="text-xs text-gray-500 mt-1">Formato SIGLA-NAVE-AAAAMM (ej.: FRU-N06-202509). Puedes editarlo.</p>
                @error('codigo')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
    </x-card>

    <x-card titulo="Siembra y fechas estimadas">
        <div class="grid gap-5 md:grid-cols-2">
            <x-input name="fecha_siembra" label="Fecha de siembra *" type="date" :value="$fecha('fecha_siembra')" max="{{ today()->toDateString() }}" min="2018-01-01" />
            <x-select name="fecha_siembra_precision" label="Precisión de la fecha" :opciones="$P::PRECISIONES" :valor="$p->fecha_siembra_precision" />
            <x-input name="fecha_inicio_cosecha_estimada" label="Inicio estimado de la primera cosecha" type="date" :value="$fecha('fecha_inicio_cosecha_estimada')"
                     ayuda="Se usa para alertar retrasos; no se usa para calificar el desempeño." />
            <x-input name="fecha_salida_estimada" label="Salida estimada de la plantación" type="date" :value="$fecha('fecha_salida_estimada')" />
        </div>
        <p class="text-xs text-gray-500 mt-4">Si solo conoces el mes o el año de siembra, elige cualquier día y marca la precisión: se guardará como día 1 del mes o 1 de enero.</p>
    </x-card>

    <x-card titulo="Tamaño y meta productiva">
        <div class="grid gap-5 md:grid-cols-2">
            <x-input name="num_camas" label="Número de camas" type="number" min="1" step="1" :value="$p->num_camas" />
            <x-input name="num_plantas" label="Número de plantas" type="number" min="1" step="1" :value="$p->num_plantas" />
            <x-input name="meta_kg_por_cama" label="Meta (kg por cama)" type="number" min="0.01" step="0.01" :value="$p->meta_kg_por_cama" />
            <x-select name="meta_periodo" label="Período de la meta" :opciones="$P::PERIODOS_META" :valor="$p->meta_periodo" vacio="Sin definir" />
        </div>
        <x-alert tipo="aviso" class="mt-4"><strong>Provisional.</strong> Las metas por cama y su período están pendientes de validar con Amanecer. Sin meta ni camas, el IDC queda «incompleto».</x-alert>
    </x-card>

    <x-card titulo="Seguimiento">
        <div class="grid gap-5 md:grid-cols-3">
            <x-select name="responsable_id" label="Responsable" :opciones="$responsables" :valor="$p->responsable_id" vacio="Sin asignar" />
            <x-select name="estado" label="Estado *" :opciones="$P::ESTADOS" :valor="$p->estado" />
            <x-select name="etapa" label="Etapa" :opciones="$P::ETAPAS" :valor="$p->etapa" vacio="Sin definir" />
        </div>
        <div class="mt-5">
            <label for="observaciones" class="label">Observaciones</label>
            <textarea id="observaciones" name="observaciones" rows="4" maxlength="3000" class="input-modern">{{ old('observaciones', $p->observaciones) }}</textarea>
            @error('observaciones')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
    </x-card>

    <div class="flex flex-wrap gap-3">
        <button class="btn-primary">{{ $editando ? 'Guardar cambios' : 'Registrar plantación' }}</button>
        <a href="{{ $editando ? route('plantaciones.show', $p) : route('plantaciones.index') }}" class="btn-secondary">Cancelar</a>
    </div>
</form>
@endsection
