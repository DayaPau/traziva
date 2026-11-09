<?php

namespace App\Services\Plantaciones;

use App\Models\Cultivo;
use App\Models\Plantacion;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Regla única para el código de una plantación: SIGLA-NAVE-FECHA, p. ej. FRU-N06-202509.
 *  - SIGLA: sigla del cultivo (o 3 primeras letras del nombre).
 *  - NAVE:  "N" + número con dos dígitos, o "SN" si no tiene nave.
 *  - FECHA: aaaamm de la siembra (aaaa si solo se conoce el año), o "SF" sin fecha.
 * La usan el formulario y el importador de Excel.
 */
class CodigoPlantacion
{
    public static function sigla(Cultivo|string $cultivo): string
    {
        if ($cultivo instanceof Cultivo && $cultivo->sigla) {
            return strtoupper($cultivo->sigla);
        }
        $nombre = $cultivo instanceof Cultivo ? $cultivo->nombre : $cultivo;

        return strtoupper(substr(preg_replace('/[^A-Za-z]/', '', Str::ascii($nombre)), 0, 3)) ?: 'CUL';
    }

    public static function base(string $sigla, ?string $nave, ?Carbon $siembra, string $precision = 'dia'): string
    {
        $n = $nave !== null && $nave !== ''
            ? 'N'.str_pad(preg_replace('/\D/', '', $nave) ?: $nave, 2, '0', STR_PAD_LEFT)
            : 'SN';
        $f = $siembra ? $siembra->format($precision === 'anio' ? 'Y' : 'Ym') : 'SF';

        return "$sigla-$n-$f";
    }

    /** Código libre en la base de datos (agrega -2, -3… si ya existe, incluso entre retiradas). */
    public static function generar(Cultivo $cultivo, ?string $nave, ?Carbon $siembra, string $precision = 'dia'): string
    {
        $base = self::base(self::sigla($cultivo), $nave, $siembra, $precision);
        $codigo = $base;
        for ($i = 2; Plantacion::withTrashed()->where('codigo', $codigo)->exists(); $i++) {
            $codigo = "$base-$i";
        }

        return $codigo;
    }
}
