<?php

namespace App\Console\Commands;

use App\Models\Auditoria;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Cambia la persona que es coordinador/a (D-16).
 *
 * Solo puede haber UN/A coordinador/a activo/a y nunca cero, así que el reemplazo no se puede
 * hacer desde la interfaz (habría un momento con dos o con ninguno). Este comando lo hace en una
 * sola transacción: deja como coordinador/a a la persona indicada (la crea si no existe) y
 * desactiva a los/las demás coordinadores/as. Requiere acceso al servidor, que es la prueba de autoridad.
 *
 *   php artisan traziva:cambiar-coordinador --email=coordinadora@udla.edu.ec
 */
class CambiarCoordinadorCommand extends Command
{
    protected $signature = 'traziva:cambiar-coordinador
                            {--email= : Correo de la nueva persona coordinadora}
                            {--force : No pedir confirmación}';

    protected $description = 'Deja a una persona como único/a coordinador/a activo/a (la crea si no existe) y desactiva a los/las demás';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) ($this->option('email') ?: $this->ask('Correo de la nueva persona coordinadora'))));
        if (Validator::make(['email' => $email], ['email' => 'required|email:rfc|max:150'])->fails()) {
            $this->error('Correo no válido.');

            return self::FAILURE;
        }

        $rolCoordinador = Rol::where('nombre', 'coordinador')->firstOrFail();
        $usuario = User::where('email', $email)->first();
        $nuevos = [];

        if (! $usuario) {
            $this->line("No existe una cuenta con $email; se creará.");
            $nuevos['name'] = trim((string) $this->ask('Nombre completo'));
            $clave = (string) $this->secret('Contraseña (mín. 8, mayúsculas, minúsculas, números y símbolos)');
            $validacion = Validator::make(
                ['name' => $nuevos['name'], 'password' => $clave, 'password_confirmation' => (string) $this->secret('Confirmar contraseña')],
                ['name' => 'required|string|min:3|max:120', 'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()]],
            );
            if ($validacion->fails()) {
                foreach ($validacion->errors()->all() as $e) {
                    $this->error($e);
                }

                return self::FAILURE;
            }
            $nuevos['password'] = $clave;
        }

        $anteriores = User::where('rol_id', $rolCoordinador->id)->where('estado', 'activo')
            ->when($usuario, fn ($q) => $q->whereKeyNot($usuario->id))->get();

        $this->table(['Acción', 'Cuenta'], array_merge(
            [[$usuario ? 'Queda como coordinador/a' : 'Se crea como coordinador/a', $email]],
            $anteriores->map(fn ($u) => ['Se desactiva', $u->email])->all(),
        ));
        if (! $this->option('force') && ! $this->confirm('¿Confirmas el cambio de coordinador/a?')) {
            return self::FAILURE;
        }

        DB::transaction(function () use ($usuario, $email, $nuevos, $rolCoordinador, $anteriores) {
            $usuario ??= new User(['email' => $email] + $nuevos);
            $usuario->fill(['rol_id' => $rolCoordinador->id, 'estado' => 'activo'])->save();
            $anteriores->each->update(['estado' => 'inactivo']);

            Auditoria::create([
                'accion' => 'cambiar_coordinador', 'entidad' => 'users', 'entidad_id' => $usuario->id,
                'valores_antes' => ['coordinadores' => $anteriores->pluck('email')->all()],
                'valores_despues' => ['coordinador' => $email],
            ]);
        });

        $this->info("Listo: $email es el/la único/a coordinador/a activo/a.");

        return self::SUCCESS;
    }
}
