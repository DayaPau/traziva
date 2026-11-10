<?php

namespace Database\Seeders;

use App\Models\Auditoria;
use App\Models\CategoriaCalidad;
use App\Models\Cosecha;
use App\Models\Cultivo;
use App\Models\Plantacion;
use App\Models\TipoLabor;
use App\Services\Idc\IdcCalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

/**
 * DATOS SINTÉTICOS DE DEMOSTRACIÓN (no son producción real de Amanecer).
 *
 * Crea 3 plantaciones TEST-… con es_prueba = true para mostrar los tres casos del IDC:
 *   TEST-01-ALTO       → meta, camas, cosechas de buena calidad, poca merma y registros constantes.
 *   TEST-02-MEDIO      → producción por debajo de la meta, calidad media, más merma y menos registros.
 *   TEST-03-INCOMPLETO → sin meta ni cosechas: el IDC explica qué falta.
 *
 * Las fechas son relativas al día en que se ejecuta (el IDC evalúa hasta "hoy").
 * Uso:    php artisan db:seed --class=DatosPruebaSeeder
 * Borrar: php artisan traziva:borrar-prueba
 */
class DatosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        // Volver a sembrar reemplaza los datos de prueba anteriores (nunca toca los reales).
        Artisan::call('traziva:borrar-prueba', ['--force' => true]);

        $hoy = Carbon::today();
        $calidad = CategoriaCalidad::orderBy('orden')->pluck('id')->values(); // [menor, …, mayor]
        $tipoLabor = TipoLabor::where('activo', true)->value('id') ?? TipoLabor::create(['nombre' => 'Deshierbe'])->id;
        $cultivo = fn (string $n) => Cultivo::firstOrCreate(['nombre' => $n])->id;

        Auditoria::$silenciada = true; // la carga sintética no ensucia la auditoría
        try {
            // --- Caso ALTO ---------------------------------------------------------------
            $alto = $this->plantacion('TEST-01-ALTO', $cultivo('Frutilla'), $hoy->copy()->subDays(120), [
                'variedad' => 'Albión (prueba)', 'num_camas' => 5, 'num_plantas' => 200,
                'meta_kg_por_cama' => 10, 'meta_periodo' => 'mensual', 'etapa' => 'cosecha',
                'fecha_inicio_cosecha_estimada' => $hoy->copy()->subDays(60),
            ]);
            $this->labores($alto, $tipoLabor, $hoy->copy()->subDays(118), 10, 12);
            // 8 cortes semanales (≈ 85 kg) de calidad alta y ~5 % de merma.
            foreach (range(0, 7) as $i) {
                $this->cosecha($alto, $hoy->copy()->subDays(56 - $i * 8), 10.6, 0.5, $calidad[$i % 3 === 0 ? 1 : count($calidad) - 1]);
            }

            // --- Caso MEDIO ----------------------------------------------------------------
            $medio = $this->plantacion('TEST-02-MEDIO', $cultivo('Mora'), $hoy->copy()->subDays(120), [
                'variedad' => 'Castilla (prueba)', 'num_camas' => 5, 'num_plantas' => 150,
                'meta_kg_por_cama' => 10, 'meta_periodo' => 'mensual', 'etapa' => 'cosecha',
                'fecha_inicio_cosecha_estimada' => $hoy->copy()->subDays(60),
            ]);
            $this->labores($medio, $tipoLabor, $hoy->copy()->subDays(115), 20, 6);
            // 8 cortes (≈ 60 kg) de calidad media y ~12 % de merma.
            foreach (range(0, 7) as $i) {
                $this->cosecha($medio, $hoy->copy()->subDays(56 - $i * 8), 7.5, 0.9, $calidad[intdiv(count($calidad) - 1, 2)]);
            }

            // --- Caso INCOMPLETO -------------------------------------------------------------
            $incompleto = $this->plantacion('TEST-03-INCOMPLETO', $cultivo('Uvilla'), $hoy->copy()->subDays(45), [
                'variedad' => 'Colombiana (prueba)', 'num_camas' => 4, 'etapa' => 'crecimiento',
            ]);
            $this->labores($incompleto, $tipoLabor, $hoy->copy()->subDays(40), 14, 3);
        } finally {
            Auditoria::$silenciada = false;
        }

        $calc = app(IdcCalculator::class);
        foreach ([$alto, $medio, $incompleto] as $p) {
            $r = $calc->calcular($p, 'parcial');
            $this->command?->info(sprintf('%-20s IDC %s (%s)', $p->codigo, $r->puntaje ?? '—', $r->categoria));
        }
    }

    private function plantacion(string $codigo, int $cultivoId, Carbon $siembra, array $extra): Plantacion
    {
        return Plantacion::create($extra + [
            'codigo' => $codigo, 'cultivo_id' => $cultivoId, 'nave_id' => null,
            'fecha_siembra' => $siembra, 'fecha_siembra_precision' => 'dia', 'estado' => 'activa',
            'observaciones' => 'DATOS DE PRUEBA sintéticos para demostrar el IDC. No representan producción real de Amanecer.',
            'es_prueba' => true,
        ]);
    }

    private function labores(Plantacion $p, int $tipo, Carbon $desde, int $cadaDias, int $cantidad): void
    {
        foreach (range(0, $cantidad - 1) as $i) {
            $fecha = $desde->copy()->addDays($i * $cadaDias);
            if ($fecha->isFuture()) {
                break;
            }
            $p->labores()->create(['tipo_labor_id' => $tipo, 'fecha' => $fecha, 'descripcion' => 'Labor de prueba']);
        }
    }

    private function cosecha(Plantacion $p, Carbon $fecha, float $kg, float $merma, ?int $calidad): void
    {
        // Sin eventos: el IDC se calcula una sola vez al final (no uno por cosecha).
        Cosecha::withoutEvents(fn () => Cosecha::create([
            'plantacion_id' => $p->id, 'fecha_cosecha' => $fecha, 'cantidad_kg' => $kg,
            'merma_kg' => $merma, 'categoria_calidad_id' => $calidad,
        ]));
    }
}
