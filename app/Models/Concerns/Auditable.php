<?php

namespace App\Models\Concerns;

use App\Models\Auditoria;

/**
 * Registra en la tabla `auditoria` (solo inserción) quién creó, modificó o
 * eliminó un registro crítico (RNF-07).
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn ($m) => $m->auditar('crear', null, $m->getAttributes()));
        static::updated(fn ($m) => $m->auditar('modificar', array_intersect_key($m->getOriginal(), $m->getChanges()), $m->getChanges()));
        static::deleted(fn ($m) => $m->auditar('eliminar', $m->getOriginal(), null));
    }

    protected function auditar(string $accion, ?array $antes, ?array $despues): void
    {
        if (Auditoria::$silenciada) {
            return;
        }

        Auditoria::create([
            'user_id'         => auth()->id(),
            'accion'          => $accion,
            'entidad'         => $this->getTable(),
            'entidad_id'      => $this->getKey(),
            'valores_antes'   => $antes,
            'valores_despues' => $despues,
        ]);
    }
}
