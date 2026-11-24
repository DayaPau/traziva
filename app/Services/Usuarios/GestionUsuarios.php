<?php

namespace App\Services\Usuarios;

use App\Models\Auditoria;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de negocio de la gestión de usuarios (S5-04, D-16).
 *
 * Invariantes:
 *  1. Nadie puede desactivarse ni quitarse el rol a sí mismo (evita quedar fuera por error).
 *  2. Siempre debe quedar al menos un/a coordinador/a activo/a.
 *  3. Límite de cuentas ACTIVAS por rol (config traziva.registro.limites: 1 coordinador/a, 2 responsables).
 *     Con 1 y 2 juntas, siempre hay exactamente un/a coordinador/a; el cambio de persona se hace
 *     con `php artisan traziva:cambiar-coordinador`.
 *
 * La auditoría de crear/modificar la hace el trait Auditable del modelo User (sin contraseña).
 * Aquí se registran además las acciones con nombre propio: «aprobar» y «restablecer_clave».
 */
class GestionUsuarios
{
    public function crear(array $datos): User
    {
        $datos += ['estado' => 'activo'];
        $this->verificarCupo((int) $datos['rol_id'], $datos['estado']);

        return User::create($datos);
    }

    public function actualizar(User $usuario, array $datos, User $actor): User
    {
        $rolId = (int) ($datos['rol_id'] ?? $usuario->rol_id);
        $estado = $datos['estado'] ?? $usuario->estado;
        $this->verificarInvariantes($usuario, $rolId, $estado, $actor);
        // El cupo solo se revisa si la cuenta pasa a ocupar uno (cambia de rol o se activa).
        if ($rolId !== (int) $usuario->rol_id || $usuario->estado !== 'activo') {
            $this->verificarCupo($rolId, $estado, $usuario);
        }

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

    /** ¿Queda lugar para otra cuenta activa de este rol? (true si el rol no tiene límite) */
    public function hayCupo(string $rolNombre, ?User $excepto = null): bool
    {
        $limite = config("traziva.registro.limites.$rolNombre");

        return $limite === null || User::whereHas('rol', fn ($q) => $q->where('nombre', $rolNombre))
            ->where('estado', 'activo')
            ->when($excepto, fn ($q) => $q->whereKeyNot($excepto->id))
            ->count() < $limite;
    }

    private function verificarCupo(int $rolId, string $estado, ?User $usuario = null): void
    {
        $rol = Rol::find($rolId);
        if ($estado !== 'activo' || ! $rol || $this->hayCupo($rol->nombre, $usuario)) {
            return;
        }

        $limite = config("traziva.registro.limites.{$rol->nombre}");
        $nombre = mb_strtolower(config("traziva.roles.{$rol->nombre}", $rol->nombre));
        throw ValidationException::withMessages([
            'rol_id' => "Ya existe el máximo de cuentas activas de $nombre ($limite). Desactiva una antes de activar otra.",
        ]);
    }

    private function verificarInvariantes(User $usuario, int $rolId, string $estado, User $actor): void
    {
        $idCoordinador = (int) Rol::where('nombre', 'coordinador')->value('id');
        $rolActual = (int) $usuario->getOriginal('rol_id');
        $dejaDeSerCoordinadorActivo = $rolActual === $idCoordinador && $usuario->getOriginal('estado') === 'activo'
            && ($rolId !== $idCoordinador || $estado !== 'activo');

        if ($usuario->is($actor) && ($estado !== 'activo' || $rolId !== $rolActual)) {
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
