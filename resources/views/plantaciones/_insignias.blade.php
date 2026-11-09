{{-- Insignias de estado (administrativo) y etapa (fenológica). Requiere $p. --}}
@php
    $colorEstado = ['activa' => 'verde', 'inactiva' => 'gris', 'finalizada' => 'tierra'];
    $colorEtapa = ['crecimiento' => 'azul', 'floracion' => 'amarillo', 'cosecha' => 'verde'];
@endphp
<div class="flex flex-wrap gap-1">
    <x-badge :color="$colorEstado[$p->estado] ?? 'gris'">{{ \App\Models\Plantacion::ESTADOS[$p->estado] ?? $p->estado }}</x-badge>
    @if($p->etapa)
        <x-badge :color="$colorEtapa[$p->etapa] ?? 'gris'">{{ \App\Models\Plantacion::ETAPAS[$p->etapa] }}</x-badge>
    @endif
</div>
