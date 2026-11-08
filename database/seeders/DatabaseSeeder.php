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

        $pass = env('TRAZIVA_SEED_PASSWORD') ?: Str::random(12);
        $creados = 0;
        foreach (['coordinador', 'responsable', 'docente', 'estudiante'] as $r) {
            $u = User::firstOrCreate(['email' => "$r@traziva.test"], [
                'name' => ucfirst($r) . ' Demo', 'password' => $pass,
                'rol_id' => Rol::where('nombre', $r)->value('id'), 'estado' => 'activo',
            ]);
            $creados += $u->wasRecentlyCreated ? 1 : 0;
        }
        // Solo se informa la contraseña si se crearon usuarios; los existentes conservan la suya.
        $this->command?->info($creados
            ? "Usuarios demo creados: <rol>@traziva.test / contraseña: $pass"
            : 'Los usuarios demo ya existían; conservan su contraseña.');
    }
}
