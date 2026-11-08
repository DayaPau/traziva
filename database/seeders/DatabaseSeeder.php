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
        $roles = ['coordinador' => 'Administra usuarios, catálogos y reportes',
            'responsable' => 'Responsable de producción; registra y valida',
            'docente' => 'Docente; registra y consulta',
            'estudiante' => 'Estudiante; registra con acceso temporal'];
        foreach ($roles as $n => $d) {
            Rol::firstOrCreate(['nombre' => $n], ['descripcion' => $d]);
        }

        // Catálogo PROVISIONAL: confirmar categorías reales con Amanecer.
        foreach (['Aceptable', 'Buena', 'Excelente'] as $i => $n) {
            CategoriaCalidad::firstOrCreate(['nombre' => $n], ['orden' => $i + 1]);
        }

        $pass = env('TRAZIVA_SEED_PASSWORD') ?: Str::random(12);
        foreach (['coordinador', 'responsable', 'docente', 'estudiante'] as $r) {
            User::firstOrCreate(['email' => "$r@traziva.test"], [
                'name' => ucfirst($r) . ' Demo', 'password' => $pass,
                'rol_id' => Rol::where('nombre', $r)->value('id'), 'estado' => 'activo',
            ]);
        }
        $this->command?->info("Usuarios demo: <rol>@traziva.test / contraseña: $pass");
    }
}
