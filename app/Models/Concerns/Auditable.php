<?php

namespace App\Models\Concerns;

use App\Models\Auditoria;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn ($m) => $m->auditar('crear', null, $m->datosAuditables($m->getAttributes())));
        static::updated(function ($m) {
            $cambios = $m->datosAuditables($m->getChanges());
            // Si solo cambiaron campos excluidos (p. ej. remember_token) no hay nada que auditar.
            if (! array_diff_key($cambios, ['updated_at' => true])) {
                return;
            }
            $m->auditar('modificar', array_intersect_key($m->getOriginal(), $cambios), $cambios);
        });
        static::deleted(fn ($m) => $m->auditar('eliminar', $m->datosAuditables($m->getOriginal()), null));
    }

    /** Quita los campos que el modelo declare en $auditExcluir (contraseñas, tokens). */
    protected function datosAuditables(array $datos): array
    {
        return array_diff_key($datos, array_flip($this->auditExcluir ?? []));
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
