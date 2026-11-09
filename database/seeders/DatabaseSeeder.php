<?php

namespace Database\Seeders;

use App\Models\CategoriaCalidad;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Ver matriz de permisos en docs/decisiones.md (D-03).
        $roles = ['coordinador' => 'Superadministrador: usuarios, catálogos, registros, reportes y auditoría',
            'responsable' => 'Responsable de producción: crea y modifica plantaciones y registros',
            'docente' => 'Docente/investigador: solo consulta y descarga reportes',
            'estudiante' => 'Estudiante/practicante: solo consulta, con acceso temporal'];
        foreach ($roles as $n => $d) {
            Rol::updateOrCreate(['nombre' => $n], ['descripcion' => $d]);
        }

        // Catálogo PROVISIONAL: confirmar categorías reales con Amanecer.
        foreach (['Aceptable', 'Buena', 'Excelente'] as $i => $n) {
            CategoriaCalidad::firstOrCreate(['nombre' => $n], ['orden' => $i + 1]);
        }

        // Usuarios demo (@traziva.test). Con TRAZIVA_SEED_PASSWORD en .env, se les aplica esa contraseña
        // también si ya existían (así todo el equipo prueba con la misma). Nunca toca cuentas reales.
        $desdeEnv = (string) env('TRAZIVA_SEED_PASSWORD', '');
        $pass = $desdeEnv !== '' ? $desdeEnv : Str::random(12);
        $creados = 0;
        foreach (['coordinador', 'responsable', 'docente', 'estudiante'] as $r) {
            $u = User::firstOrCreate(['email' => "$r@traziva.test"], [
                'name' => ucfirst($r) . ' Demo', 'password' => $pass,
                'rol_id' => Rol::where('nombre', $r)->value('id'), 'estado' => 'activo',
            ]);
            if ($u->wasRecentlyCreated) {
                $creados++;
            } elseif ($desdeEnv !== '') {
                $u->update(['password' => $desdeEnv]);
            }
        }

        // La contraseña tomada de .env no se imprime (no dejar secretos en consola ni logs).
        $this->command?->info(match (true) {
            $desdeEnv !== '' => 'Usuarios demo <rol>@traziva.test listos con la contraseña de TRAZIVA_SEED_PASSWORD (.env).',
            $creados > 0 => "Usuarios demo creados: <rol>@traziva.test / contraseña: $pass",
            default => 'Los usuarios demo ya existían; conservan su contraseña.',
        });
    }
}
