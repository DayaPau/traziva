<?php

namespace App\Console\Commands;

use App\Models\Plantacion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Elimina definitivamente los datos sintéticos de demostración.
 * Solo toca plantaciones con es_prueba = true Y código TEST-…; sus labores, aplicaciones,
 * eventos, cosechas e IDC se borran en cascada (claves foráneas). Los datos reales no se tocan.
 */
class BorrarDatosPruebaCommand extends Command
{
    protected $signature = 'traziva:borrar-prueba {--force : No pedir confirmación}';

    protected $description = 'Elimina las plantaciones de prueba (es_prueba = true, código TEST-…) y todos sus registros';

    public function handle(): int
    {
        $query = Plantacion::withTrashed()->where('es_prueba', true)->where('codigo', 'like', 'TEST-%');
        $codigos = $query->pluck('codigo');

        if ($codigos->isEmpty()) {
            $this->info('No hay datos de prueba que borrar.');

            return self::SUCCESS;
        }

        $this->line('Se eliminarán: '.$codigos->join(', '));
        if (! $this->option('force') && ! $this->confirm('¿Eliminar definitivamente estas plantaciones de prueba y sus registros?')) {
            return self::FAILURE;
        }

        DB::transaction(fn () => $query->get()->each->forceDelete());
        $this->info("Eliminadas {$codigos->count()} plantaciones de prueba. Los datos reales no se modificaron.");

        return self::SUCCESS;
    }
}
