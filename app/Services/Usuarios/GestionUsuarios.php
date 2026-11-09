<?php

namespace App\Services\Usuarios;

use App\Models\Auditoria;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de negocio de la gestión de usuarios (S5-04).
 *
 * Invariantes:
 *  1. Nadie puede desactivarse ni quitarse el rol de coordinador/a a sí mismo (evita quedar fuera por error).
 *  2. Siempre debe quedar al menos un/a coordinador/a activo/a (si no, nadie podría administrar ni aprobar cuentas).
 *
 * La auditoría de crear/modificar la hace el trait Auditable del modelo User (sin contraseña).
 * Aquí se registran además las acciones con nombre propio: «aprobar» y «restablecer_clave».
 */
class GestionUsuarios
{
    public function crear(array $datos): User
    {
        return User::create($datos + ['estado' => 'activo']);
    }

    public function actualizar(User $usuario, array $datos, User $actor): User
    {
        $this->verificarInvariantes($usuario, $datos['rol_id'] ?? $usuario->rol_id, $datos['estado'] ?? $usuario->estado, $actor);

        return DB::transaction(function () use ($usuario, $datos) {
            $estabaPendiente = $usuario->estado === 'pendiente';
            $usuario->update($datos);
            if ($estabaPendiente && $usuario->estado === 'activo') {
                $this->registrar('aprobar', $usuario);
            }

            return $usuario;
        });
    }

    /** Botón rápido del listado: pendiente → activo (aprobar), activo ↔ inactivo. */
    public function alternarEstado(User $usuario, User $actor): User
    {
        $nuevo = $usuario->estado === 'activo' ? 'inactivo' : 'activo';

        return $this->actualizar($usuario, ['estado' => $nuevo], $actor);
    }

    /** El/la coordinador/a fija una contraseña nueva; se registra la acción, nunca el valor. */
    public function restablecerClave(User $usuario, string $nueva): void
    {
        DB::transaction(function () use ($usuario, $nueva) {
            $usuario->forceFill(['password' => $nueva, 'remember_token' => null])->saveQuietly();
            $this->registrar('restablecer_clave', $usuario);
        });
    }

    private function verificarInvariantes(User $usuario, int|string $rolId, string $estado, User $actor): void
    {
        $idCoordinador = (int) Rol::where('nombre', 'coordinador')->value('id');
        $rolActual = (int) $usuario->getOriginal('rol_id');
        $dejaDeSerCoordinadorActivo = $rolActual === $idCoordinador && $usuario->getOriginal('estado') === 'activo'
            && ((int) $rolId !== $idCoordinador || $estado !== 'activo');

        if ($usuario->is($actor) && ($estado !== 'activo' || (int) $rolId !== $rolActual)) {
            throw ValidationException::withMessages(['estado' => 'No puedes desactivar tu propia cuenta ni cambiar tu propio rol.']);
        }

        if ($dejaDeSerCoordinadorActivo
            && User::where('rol_id', $idCoordinador)->where('estado', 'activo')->whereKeyNot($usuario->id)->doesntExist()) {
            throw ValidationException::withMessages(['rol_id' => 'Debe quedar al menos un/a coordinador/a activo/a en el sistema.']);
        }
    }

    private function registrar(string $accion, User $usuario): void
    {
        Auditoria::create([
            'user_id' => auth()->id(),
            'accion' => $accion,
            'entidad' => 'users',
            'entidad_id' => $usuario->id,
            'valores_despues' => $accion === 'aprobar' ? ['estado' => 'activo', 'rol_id' => $usuario->rol_id] : null,
        ]);
    }
}
