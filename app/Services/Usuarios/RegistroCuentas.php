<?php

namespace App\Services\Usuarios;

use App\Models\Auditoria;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registro público de cuentas (D-16).
 *
 *  - Docente y estudiante: se registran solos y la cuenta queda activa (solo consultan).
 *  - Coordinador/a y responsable: además necesitan el código de verificación de su rol
 *    (config traziva.registro.codigos, definido en el .env) y que haya cupo (límite de cuentas activas).
 */
class RegistroCuentas
{
    public function __construct(private GestionUsuarios $gestion) {}

    /** Roles que se ofrecen en el formulario: los de consulta siempre; los privilegiados solo si tienen código configurado. */
    public function rolesDisponibles(): Collection
    {
        return Rol::orderBy('id')->get()
            ->filter(fn (Rol $r) => ! $this->requiereCodigo($r->nombre) || $this->codigoConfigurado($r->nombre))
            ->values();
    }

    public function requiereCodigo(string $rol): bool
    {
        return array_key_exists($rol, config('traziva.registro.codigos', []));
    }

    public function codigoValido(string $rol, ?string $codigo): bool
    {
        $esperado = (string) config("traziva.registro.codigos.$rol", '');

        // hash_equals: comparación en tiempo constante (no revela cuántos caracteres coinciden).
        return $esperado !== '' && $codigo !== null && hash_equals($esperado, trim($codigo));
    }

    public function hayCupo(string $rol): bool
    {
        return $this->gestion->hayCupo($rol);
    }

    /** Crea la cuenta activa y la audita. El cupo se vuelve a comprobar dentro de la transacción. */
    public function registrar(array $datos): User
    {
        return DB::transaction(function () use ($datos) {
            $rol = Rol::findOrFail($datos['rol_id']);
            if (! $this->hayCupo($rol->nombre)) {
                throw ValidationException::withMessages(['rol_id' => 'Ya no hay cupo para ese rol. Elige otro o contacta al coordinador/a.']);
            }

            // Sin eventos: se audita una sola vez, con la acción explícita «registro».
            $user = User::withoutEvents(fn () => User::create([
                'name' => $datos['name'], 'email' => $datos['email'], 'password' => $datos['password'],
                'rol_id' => $rol->id, 'estado' => 'activo',
            ]));

            Auditoria::create([
                'user_id' => $user->id,
                'accion' => 'registro',
                'entidad' => 'users',
                'entidad_id' => $user->id,
                'valores_despues' => ['name' => $user->name, 'email' => $user->email, 'rol_id' => $rol->id,
                    'estado' => 'activo', 'con_codigo' => $this->requiereCodigo($rol->nombre)],
            ]);

            return $user;
        });
    }

    private function codigoConfigurado(string $rol): bool
    {
        return (string) config("traziva.registro.codigos.$rol", '') !== '';
    }
}
