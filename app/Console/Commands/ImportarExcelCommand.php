<?php

namespace App\Console\Commands;

use App\Services\Importacion\ExcelCultivosImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportarExcelCommand extends Command
{
    protected $signature = 'traziva:importar {archivo : Ruta del Excel de Amanecer (.xlsx)}
        {--dry-run : Procesa y reporta sin guardar nada}
        {--asignar=ultima : A qué plantación van las labores/aplicaciones sin nave: ultima|todas}
        {--anio-minimo=2018 : Fechas anteriores a este año se rechazan por probable error}';

    protected $description = 'Importa el formato PRODUCCIÓN CULTIVOS de Amanecer con reporte de aceptadas, advertencias y rechazadas';

    public function handle(): int
    {
        $ruta = $this->argument('archivo');
        if (! is_file($ruta)) {
            $this->error("No existe el archivo: $ruta");
            return self::FAILURE;
        }
        if (! in_array($this->option('asignar'), ['ultima', 'todas'], true)) {
            $this->error('--asignar debe ser "ultima" o "todas".');
            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $res = (new ExcelCultivosImporter($this->option('asignar'), (int) $this->option('anio-minimo')))->importar($ruta, $dry);

        $this->info($dry ? 'SIMULACIÓN (no se guardó nada)' : 'Importación terminada');
        $this->table(['Concepto', 'Cantidad'], collect($res['contadores'])->map(fn ($v, $k) => [$k, $v])->values()->all());

        $porNivel = collect($res['reporte'])->groupBy('nivel');
        $this->line(sprintf('Rechazadas: %d · Advertencias: %d', $porNivel->get('rechazada', collect())->count(), $porNivel->get('advertencia', collect())->count()));

        if ($res['reporte']) {
            $this->table(['Nivel', 'Hoja', 'Ref.', 'Detalle'], array_map(fn ($f) => array_values($f), $res['reporte']));

            $nombre = 'importaciones/importacion-' . now()->format('Ymd-His') . ($dry ? '-simulacion' : '') . '.csv';
            $csv = "\xEF\xBB\xBFnivel;hoja;ref;detalle\n" . collect($res['reporte'])->map(fn ($f) => implode(';', array_map(fn ($v) => '"' . str_replace('"', '""', $v) . '"', $f)))->implode("\n");
            Storage::disk('local')->put($nombre, $csv);
            $this->line('Reporte guardado en storage/app/private/' . $nombre);
        }

        return self::SUCCESS;
    }
}
