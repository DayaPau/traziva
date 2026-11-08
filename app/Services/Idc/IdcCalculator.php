<?php

namespace App\Services\Idc;

use App\Models\CategoriaCalidad;
use App\Models\IdcCalculo;
use App\Models\Plantacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Índice de Desempeño del Cultivo (IDC) - referencial, basado en reglas.
 * No es un diagnóstico agronómico ni genera recomendaciones.
 *
 * Cada cálculo se guarda con sus variables (crudas, normalizadas y peso) para
 * poder explicar cómo se obtuvo el resultado y reproducirlo por versión de fórmula.
 * FÓRMULA BORRADOR: pesos y reglas en config/traziva.php, pendientes de validación.
 */
class IdcCalculator
{
    public function calcular(Plantacion $p, string $tipo = 'parcial', ?int $userId = null): IdcCalculo
    {
        $cfg = config('traziva.idc');
        $cosechas = $p->cosechas()->orderBy('fecha_cosecha')->get();

        $faltantes = $this->datosFaltantes($p, $cosechas);

        if ($faltantes) {
            return $this->guardar($p, $tipo, null, null, null, 'incompleto', $faltantes, [], $cfg['version'], $userId);
        }

        // ---- Periodo evaluado ----
        $desde = $p->fecha_inicio_cosecha_real ?? $cosechas->first()->fecha_cosecha;
        $hasta = $tipo === 'final'
            ? ($p->fecha_salida_real ?? $cosechas->last()->fecha_cosecha)
            : Carbon::today();
        if ($hasta->lt($desde)) {
            $hasta = $cosechas->last()->fecha_cosecha;
        }
        $dias = max(1, $desde->diffInDays($hasta) + 1);

        // ---- Rendimiento: producción real vs meta del periodo ----
        $metaBase = (float) $p->meta_kg_por_cama * $p->num_camas;
        $esperado = match ($p->meta_periodo) {
            'anual'   => $metaBase * $dias / 365,
            'mensual' => $metaBase * $dias / 30.4375,
            default   => $metaBase,
        };
        $real = $cosechas->sum('cantidad_kg');
        $rendimiento = min(100, $esperado > 0 ? $real / $esperado * 100 : 0);

        // ---- Calidad: promedio ponderado por kilos del orden de la categoría ----
        $maxOrden = max(1, (int) CategoriaCalidad::max('orden'));
        $minOrden = (int) CategoriaCalidad::min('orden');
        $rango = max(1, $maxOrden - $minOrden);
        $calidadCruda = $cosechas->sum(fn ($c) => $c->cantidad_kg * $c->calidad->orden) / max(0.001, $real);
        $calidad = $maxOrden === $minOrden ? 100 : ($calidadCruda - $minOrden) / $rango * 100;

        // ---- Aprovechamiento: aprovechable / cosechado (control de merma) ----
        $aprovechable = $cosechas->sum('cantidad_aprovechable_kg');
        $aprovechamiento = $real > 0 ? $aprovechable / $real * 100 : 0;

        // ---- Constancia: días con registros vs los esperados por frecuencia ----
        $inicioSeg = $p->fecha_siembra ?? $desde;
        $fechas = collect()
            ->merge($p->labores()->whereBetween('fecha', [$inicioSeg, $hasta])->pluck('fecha'))
            ->merge($p->aplicaciones()->whereBetween('fecha_aplicacion', [$inicioSeg, $hasta])->pluck('fecha_aplicacion'))
            ->merge($p->eventos()->whereBetween('fecha_evento', [$inicioSeg, $hasta])->pluck('fecha_evento'))
            ->map(fn ($f) => Carbon::parse($f)->toDateString())->unique()->count();
        $esperados = max(1, (int) floor(($inicioSeg->diffInDays($hasta) + 1) / $cfg['frecuencia_registro_dias']));
        $constancia = min(100, $fechas / $esperados * 100);

        // ---- Estimación de cosecha (informativa, peso 0): desviación en días ----
        $desviacion = ($p->fecha_inicio_cosecha_estimada && $p->fecha_inicio_cosecha_real)
            ? $p->fecha_inicio_cosecha_estimada->diffInDays($p->fecha_inicio_cosecha_real, false)
            : null;

        $variables = [
            'rendimiento'        => [round($real, 3), $rendimiento],
            'calidad'            => [round($calidadCruda, 3), $calidad],
            'aprovechamiento'    => [round($aprovechable, 3), $aprovechamiento],
            'constancia'         => [$fechas, $constancia],
            'estimacion_cosecha' => [$desviacion, null],
        ];

        $pesos = $cfg['pesos'];
        $suma = array_sum(array_intersect_key($pesos, array_filter($variables, fn ($v) => $v[1] !== null)));
        $puntaje = 0;
        foreach ($variables as $nombre => [$crudo, $norm]) {
            if ($norm !== null) {
                $puntaje += $norm * $pesos[$nombre];
            }
        }
        $puntaje = round($puntaje / max(0.0001, $suma), 2);

        $categoria = $puntaje >= $cfg['rangos']['alto'] ? 'alto' : ($puntaje >= $cfg['rangos']['medio'] ? 'medio' : 'bajo');

        return $this->guardar($p, $tipo, $desde, $hasta, $puntaje, $categoria, null, $variables, $cfg['version'], $userId);
    }

    /** Devuelve la lista de datos que faltan para poder calcular (vacía si se puede). */
    public function datosFaltantes(Plantacion $p, $cosechas): array
    {
        $f = [];
        if (! $p->meta_kg_por_cama || ! $p->meta_periodo) {
            $f[] = 'Meta productiva (kg por cama y su periodo)';
        }
        if (! $p->num_camas) {
            $f[] = 'Número de camas';
        }
        if ($cosechas->isEmpty()) {
            $f[] = 'Al menos una cosecha registrada';
        } elseif ($cosechas->contains(fn ($c) => ! $c->categoria_calidad_id)) {
            $f[] = 'Calidad referencial en todas las cosechas';
        }
        return $f;
    }

    private function guardar(Plantacion $p, string $tipo, $desde, $hasta, ?float $puntaje, string $categoria, ?array $faltantes, array $variables, string $version, ?int $userId): IdcCalculo
    {
        return DB::transaction(function () use ($p, $tipo, $desde, $hasta, $puntaje, $categoria, $faltantes, $variables, $version, $userId) {
            $calculo = IdcCalculo::create([
                'plantacion_id'   => $p->id,
                'tipo'            => $tipo,
                'periodo_desde'   => $desde,
                'periodo_hasta'   => $hasta,
                'puntaje'         => $puntaje,
                'categoria'       => $categoria,
                'datos_faltantes' => $faltantes,
                'version_formula' => $version,
                'calculado_por'   => $userId,
                'calculado_en'    => now(),
            ]);

            $pesos = config('traziva.idc.pesos');
            foreach ($variables as $nombre => [$crudo, $norm]) {
                $calculo->variables()->create([
                    'variable'          => $nombre,
                    'valor_crudo'       => $crudo,
                    'valor_normalizado' => $norm === null ? null : round($norm, 2),
                    'peso'              => $pesos[$nombre],
                ]);
            }

            return $calculo;
        });
    }
}
