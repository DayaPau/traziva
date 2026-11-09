<?php

namespace App\Models\Concerns;

use App\Models\Auditoria;

/**
 * Registra en la tabla `auditoria` (solo inserción) quién creó, modificó o
 * eliminó un registro crítico (RNF-07).
 *
 * Nunca guarda los atributos ocultos del modelo ($hidden: contraseña, token de sesión)
 * ni deja filas vacías cuando solo cambian campos técnicos (updated_at, remember_token).
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

        $excluir = array_flip([...$this->getHidden(), 'created_at', 'updated_at', 'deleted_at']);
        $antes = $antes === null ? null : array_diff_key($antes, $excluir);
        $despues = $despues === null ? null : array_diff_key($despues, $excluir);

        if ($accion === 'modificar' && ! $despues) {
            return; // solo cambiaron campos técnicos u ocultos
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
