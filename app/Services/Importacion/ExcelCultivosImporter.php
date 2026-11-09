<?php

namespace App\Services\Importacion;

use App\Models\Aplicacion;
use App\Models\Auditoria;
use App\Models\Cultivo;
use App\Models\Insumo;
use App\Models\LaborCultural;
use App\Models\Nave;
use App\Models\PlagaEnfermedad;
use App\Models\Plantacion;
use App\Models\TipoLabor;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Services\Plantaciones\CodigoPlantacion;

/**
 * Importa el formato "PRODUCCIÓN CULTIVOS" de Amanecer (una hoja por cultivo).
 *
 *  - Una fila de encabezado (cultivo, camas, nave, plantas, siembra, cosecha, salida) genera
 *    una plantación por cada nave. Cuando una celda mezcla varias naves/fechas se reparte por posición.
 *  - Las labores y aplicaciones del Excel no indican nave: se asignan según $asignar
 *    ("ultima" = plantación sembrada más recientemente, "todas") y queda constancia en el reporte.
 *  - Nada se corrige en silencio: lo dudoso genera "advertencia" y lo inválido queda "rechazada".
 *  - Es idempotente: reimportar el mismo archivo no duplica registros (hash de origen).
 */
class ExcelCultivosImporter
{
    private const PLAGAS = ['PULGON', 'TRIPS', 'ACAROS', 'PALOMILLA', 'BABOSA', 'GUSANO'];
    private const ENFERMEDADES = ['OIDIO', 'VELLOSO', 'LANCHA', 'BOTRITIS'];

    private const MESES = ['ene' => 1, 'feb' => 2, 'mar' => 3, 'abr' => 4, 'may' => 5, 'jun' => 6,
        'jul' => 7, 'ago' => 8, 'sep' => 9, 'set' => 9, 'oct' => 10, 'nov' => 11, 'dic' => 12];

    private const NOMBRES = [
        'tomate de arbol' => 'Tomate de árbol', 'arandano' => 'Arándano', 'pulgon' => 'Pulgón',
        'acaros' => 'Ácaros', 'fertilizacion' => 'Fertilización', 'colocacion saran' => 'Colocación sarán',
    ];

    /** @var array<int,array{nivel:string,hoja:string,ref:string,mensaje:string}> */
    private array $reporte = [];
    private array $contadores = [];
    private array $hashesVistos = [];
    private array $codigosUsados = [];

    public function __construct(
        private string $asignar = 'ultima',
        private int $anioMinimo = 2018,
    ) {
    }

    public function importar(string $ruta, bool $simulacion = false): array
    {
        $libro = IOFactory::load($ruta);

        Auditoria::$silenciada = true;
        DB::beginTransaction();
        try {
            foreach ($libro->getWorksheetIterator() as $hoja) {
                $this->procesarHoja($hoja);
            }
            $this->detectarVariantesDeInsumos();

            if ($simulacion) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            Auditoria::$silenciada = false;
            throw $e;
        }
        Auditoria::$silenciada = false;

        if (! $simulacion) {
            Auditoria::create([
                'user_id' => auth()->id(), 'accion' => 'importar', 'entidad' => 'importacion_excel',
                'valores_despues' => ['archivo' => basename($ruta)] + $this->contadores,
            ]);
        }

        return ['simulacion' => $simulacion, 'contadores' => $this->contadores, 'reporte' => $this->reporte];
    }

    // ------------------------------------------------------------------ hoja
    private function procesarHoja(Worksheet $hoja): void
    {
        $titulo = trim($hoja->getTitle());
        $cultivo = Cultivo::firstOrCreate(['nombre' => $this->bonito($titulo)], ['sigla' => CodigoPlantacion::sigla($this->bonito($titulo))]);
        $this->contar($cultivo->wasRecentlyCreated ? 'cultivos_creados' : 'cultivos_existentes');

        $filaLabores = $this->filaDe($hoja, 'labores de campo');
        if (! $filaLabores) {
            $this->nota('rechazada', $titulo, '—', 'No se encontró el encabezado "Labores de campo"; hoja omitida.');
            return;
        }

        $plantaciones = $this->leerPlantaciones($hoja, $titulo, $cultivo, $filaLabores);
        if ($plantaciones === []) {
            $this->nota('rechazada', $titulo, '—', 'La hoja no tiene datos de plantación; labores y aplicaciones omitidas.');
            return;
        }

        $notas = $this->notasSueltas($hoja);
        $destino = $this->destinatarias($plantaciones);
        if ($notas) {
            $texto = 'Notas transcritas del Excel: ' . implode(' | ', $notas);
            foreach ($destino as $p) {
                if (! str_contains((string) $p->observaciones, $texto)) {
                    $p->update(['observaciones' => trim($p->observaciones . "\n" . $texto)]);
                }
            }
            $this->nota('advertencia', $titulo, 'col. K', count($notas) . ' nota(s) suelta(s) guardada(s) como observaciones de ' . $destino->first()->codigo . '.');
        }

        $minSiembra = collect($plantaciones)->pluck('fecha_siembra')->filter()->min();
        $semanales = 0;

        for ($r = $filaLabores + 1; $r <= $hoja->getHighestDataRow(); $r++) {
            $a = $this->valor($hoja, 1, $r);

            if ($a instanceof DateTimeInterface) {
                $semanales++;
            } elseif (is_string($a) && $a !== '') {
                $this->procesarLabor($hoja, $titulo, $r, $a, $cultivo, $destino, $minSiembra);
            }

            if (is_string($this->valor($hoja, 3, $r)) && $this->valor($hoja, 3, $r) !== '') {
                $this->procesarAplicacion($hoja, $titulo, $r, $destino, $minSiembra);
            }
        }

        if ($semanales > 0) {
            $this->nota('advertencia', $titulo, 'col. A', "$semanales fechas semanales sin encabezado ni datos asociados; ignoradas (confirmar con Amanecer qué registran).");
        }
    }

    /** @return Plantacion[] */
    private function leerPlantaciones(Worksheet $hoja, string $titulo, Cultivo $cultivo, int $filaLabores): array
    {
        $creadas = [];
        $prefijo = CodigoPlantacion::sigla($cultivo);

        for ($r = 2; $r < $filaLabores; $r++) {
            $raw = [];
            foreach (range(2, 7) as $c) {
                $raw[$c] = $this->valor($hoja, $c, $r);
            }
            if (count(array_filter($raw, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }

            $naves = $this->listaNaves($raw[3]);
            $plantas = $this->listaEnteros($raw[4]);
            $siembras = $this->listaFechas($raw[5]);
            $n = max(1, count($naves));
            $ref = "fila $r";

            $camas = $this->primerEntero($raw[2]);
            if (is_string($raw[2]) && $camas !== null) {
                $this->nota('advertencia', $titulo, $ref, "Camas \"{$raw[2]}\": se tomó $camas (confirmar si es por lado o total).");
            }
            if ($n > 1 && $camas !== null) {
                $this->nota('advertencia', $titulo, $ref, "Camas ($camas) corresponden a varias naves; no se asignan a cada plantación.");
            }

            [$cosecha] = $this->fechaDe($raw[6]);
            [$salida] = $this->fechaDe($raw[7]);

            for ($i = 0; $i < $n; $i++) {
                $nave = $naves[$i] ?? null;
                [$siembra, $prec] = $this->elegir($siembras, $i, $n) ?? [null, 'dia'];
                $nPlantas = count($plantas) === $n ? $plantas[$i] : ($n === 1 ? ($plantas[0] ?? null) : null);

                if ($n > 1 && count($plantas) !== $n && $plantas) {
                    $this->nota('advertencia', $titulo, $ref, 'Plantas (' . implode('+', $plantas) . ") no se pueden repartir entre $n naves; quedan sin asignar.");
                }
                if ($n > 1 && count($siembras) > 1 && count($siembras) !== $n) {
                    $this->nota('advertencia', $titulo, $ref, 'La cantidad de fechas de siembra no coincide con la de naves; revisar.');
                }
                if ($siembra && $siembra->year < $this->anioMinimo) {
                    $this->nota('advertencia', $titulo, $ref, "Fecha de siembra {$siembra->format('Y-m')} anterior a {$this->anioMinimo}: probable error de digitación; se deja sin fecha.");
                    $siembra = null;
                }

                $codigo = $this->codigo($prefijo, $nave, $siembra, $prec);
                $obs = [];
                if ($n > 1 && $camas !== null) {
                    $obs[] = "Camas totales de la fila del Excel: $camas";
                }
                if ($n > 1 && $plantas && count($plantas) !== $n) {
                    $obs[] = 'Plantas totales de la fila del Excel: ' . array_sum($plantas);
                }

                $existente = Plantacion::withTrashed()->where('codigo', $codigo)->first();
                if ($existente) {
                    $this->contar('plantaciones_existentes');
                    $creadas[] = $existente;
                    continue;
                }

                $p = Plantacion::create([
                    'codigo' => $codigo,
                    'cultivo_id' => $cultivo->id,
                    'nave_id' => $nave ? Nave::firstOrCreate(['numero' => $nave])->id : null,
                    'num_camas' => $n === 1 ? $camas : null,
                    'num_plantas' => $nPlantas,
                    'fecha_siembra' => $siembra,
                    'fecha_siembra_precision' => $prec,
                    'fecha_inicio_cosecha_estimada' => $cosecha,
                    'fecha_salida_estimada' => $salida,
                    'estado' => 'activa',
                    'observaciones' => $obs ? implode("\n", $obs) : null,
                ]);
                $this->contar('plantaciones_creadas');
                $creadas[] = $p;
            }
        }
        return $creadas;
    }

    // --------------------------------------------------------------- labores
    private function procesarLabor(Worksheet $hoja, string $titulo, int $r, string $nombre, Cultivo $cultivo, $destino, ?Carbon $minSiembra): void
    {
        $ref = "fila $r";
        $fecha = $this->fechaDe($this->valor($hoja, 2, $r))[0];
        if (! $this->fechaValida($fecha, $minSiembra, $titulo, $ref, "labor \"" . trim($nombre) . '"')) {
            $this->contar('labores_rechazadas');
            return;
        }

        $tipo = TipoLabor::firstOrCreate(['nombre' => $this->bonito($nombre)]);
        $cultivo->tiposLabor()->syncWithoutDetaching([$tipo->id]);

        foreach ($destino as $p) {
            $hash = sha1("lab|{$p->codigo}|" . $this->clave($nombre) . '|' . $fecha->toDateString());
            if (isset($this->hashesVistos[$hash])) {
                $this->nota('advertencia', $titulo, $ref, 'Labor duplicada dentro del archivo; se conserva una sola.');
                continue;
            }
            $this->hashesVistos[$hash] = true;

            $labor = LaborCultural::firstOrCreate(
                ['origen_hash' => $hash],
                ['plantacion_id' => $p->id, 'tipo_labor_id' => $tipo->id, 'fecha' => $fecha]
            );
            $this->contar($labor->wasRecentlyCreated ? 'labores_creadas' : 'labores_existentes');
        }
    }

    // ---------------------------------------------------------- aplicaciones
    private function procesarAplicacion(Worksheet $hoja, string $titulo, int $r, $destino, ?Carbon $minSiembra): void
    {
        $ref = "fila $r";
        $producto = (string) $this->valor($hoja, 3, $r);
        $plaga = (string) ($this->valor($hoja, 4, $r) ?? '');
        $dosisTxt = (string) ($this->valor($hoja, 5, $r) ?? '');
        $fecha = $this->fechaDe($this->valor($hoja, 6, $r))[0];

        $kProd = $this->clave($producto);
        $kPlaga = $this->clave($plaga);

        // Producto y plaga invertidos (p. ej. BOTRITIS en la columna de producto).
        $conocidos = array_merge(self::PLAGAS, self::ENFERMEDADES);
        if (in_array($kProd, $conocidos, true) && $kPlaga !== '' && ! in_array($kPlaga, $conocidos, true) && $kPlaga !== 'FERTILIZACION') {
            $this->nota('advertencia', $titulo, $ref, "Columnas aparentemente invertidas (producto \"$producto\", plaga \"$plaga\"); se intercambiaron.");
            [$producto, $plaga] = [$plaga, $producto];
            [$kProd, $kPlaga] = [$kPlaga, $kProd];
        }

        $dosis = $this->parseDosis($dosisTxt);
        if (! $dosis) {
            $this->nota('rechazada', $titulo, $ref, "Dosis no interpretable \"$dosisTxt\" (producto $producto); fila en cuarentena.");
            $this->contar('aplicaciones_rechazadas');
            return;
        }
        if (! $this->fechaValida($fecha, $minSiembra, $titulo, $ref, "aplicación de $producto")) {
            $this->contar('aplicaciones_rechazadas');
            return;
        }

        $esFert = $kPlaga === 'FERTILIZACION';
        $objetivo = $esFert ? 'fertilizacion' : 'control_fitosanitario';
        $plagaModelo = null;
        if (! $esFert && $kPlaga !== '') {
            $tipo = in_array($kPlaga, self::PLAGAS, true) ? 'plaga' : (in_array($kPlaga, self::ENFERMEDADES, true) ? 'enfermedad' : 'otro');
            $plagaModelo = PlagaEnfermedad::firstOrCreate(['nombre' => $this->bonito($plaga)], ['tipo' => $tipo]);
        }

        $productos = array_values(array_filter(preg_split('/\s+Y\s+/iu', trim($producto))));
        if (count($productos) > 1) {
            $this->nota('advertencia', $titulo, $ref, 'Mezcla de ' . count($productos) . " productos con una sola dosis (\"$dosisTxt\"); se aplicó la misma dosis a cada uno.");
        }

        foreach ($destino as $p) {
            $hash = sha1("apl|{$p->codigo}|{$fecha->toDateString()}|$objetivo|" . implode('+', array_map([$this, 'clave'], $productos)) . "|$dosisTxt");
            if (isset($this->hashesVistos[$hash])) {
                $this->nota('advertencia', $titulo, $ref, 'Aplicación duplicada dentro del archivo; se conserva una sola.');
                continue;
            }
            $this->hashesVistos[$hash] = true;

            if (Aplicacion::where('origen_hash', $hash)->exists()) {
                $this->contar('aplicaciones_existentes');
                continue;
            }

            $apl = Aplicacion::create([
                'plantacion_id' => $p->id,
                'fecha_aplicacion' => $fecha,
                'objetivo' => $objetivo,
                'plaga_enfermedad_id' => $plagaModelo?->id,
                'origen_hash' => $hash,
            ]);
            foreach ($productos as $nombre) {
                $insumo = Insumo::firstOrCreate(['nombre' => $this->bonito($nombre)], [
                    'categoria' => $esFert ? ($this->clave($nombre) === 'COMPOST' ? 'enmienda_organica' : 'fertilizante') : 'fitosanitario',
                ]);
                $apl->detalles()->create([
                    'insumo_id' => $insumo->id,
                    'dosis_cantidad' => $dosis['cantidad'],
                    'dosis_unidad' => $dosis['unidad'],
                    'base_tipo' => $dosis['base_tipo'],
                    'base_valor' => $dosis['base_valor'],
                    'dosis_texto_original' => Str::limit($dosisTxt, 60, ''),
                ]);
                $this->contar('detalles_creados');
            }
            $this->contar('aplicaciones_creadas');
        }
    }

    // ------------------------------------------------------------ utilidades
    private function destinatarias(array $plantaciones)
    {
        $col = collect($plantaciones);
        if ($this->asignar === 'todas' || $col->count() === 1) {
            return $col->values();
        }
        $ultima = $col->sortByDesc(fn ($p) => $p->fecha_siembra?->timestamp ?? 0)->first();
        $this->nota('advertencia', $ultima->cultivo->nombre, '—', 'El Excel no indica nave en labores/aplicaciones: se asignaron a ' . $ultima->codigo . ' (sembrada más recientemente).');
        return collect([$ultima]);
    }

    private function fechaValida(?Carbon $f, ?Carbon $minSiembra, string $hoja, string $ref, string $que): bool
    {
        if (! $f) {
            $this->nota('rechazada', $hoja, $ref, "Fecha ausente o ilegible en $que.");
            return false;
        }
        if ($f->year < $this->anioMinimo) {
            $this->nota('rechazada', $hoja, $ref, "Fecha {$f->format('d/m/Y')} en $que es anterior a {$this->anioMinimo} (probable error de año); fila en cuarentena.");
            return false;
        }
        if ($minSiembra && $f->lt($minSiembra)) {
            $this->nota('rechazada', $hoja, $ref, "Fecha {$f->format('d/m/Y')} en $que es anterior a la siembra ({$minSiembra->format('d/m/Y')}); fila en cuarentena.");
            return false;
        }
        return true;
    }

    private function parseDosis(string $t): ?array
    {
        if (! preg_match('/^\s*(\d+(?:[.,]\d+)?)\s*(GR|G|KG|CC|ML|LT|L)\s*X\s*(?:(\d+(?:[.,]\d+)?)\s*(?:LTS|LT|L)|PLANTA)\s*$/iu', $t, $m)) {
            return null;
        }
        $unidad = match (strtoupper($m[2])) {'GR', 'G' => 'g', 'KG' => 'kg', 'CC', 'ML' => 'ml', default => 'l'};
        $porPlanta = ! isset($m[3]) || $m[3] === '';
        return [
            'cantidad' => (float) str_replace(',', '.', $m[1]),
            'unidad' => $unidad,
            'base_tipo' => $porPlanta ? 'por_planta' : 'por_volumen_mezcla',
            'base_valor' => $porPlanta ? null : (float) str_replace(',', '.', $m[3]),
        ];
    }

    private function listaNaves(mixed $v): array
    {
        if ($v === null || $v === '') {
            return [];
        }
        $partes = preg_split('/\s*[,;]\s*|\s+y\s+/iu', (string) $v);
        return array_values(array_filter(array_map(fn ($p) => ltrim(trim($p), '#'), $partes), fn ($p) => $p !== ''));
    }

    private function listaEnteros(mixed $v): array
    {
        if (is_numeric($v)) {
            return [(int) $v];
        }
        preg_match_all('/\d+/', (string) $v, $m);
        return array_map('intval', $m[0]);
    }

    private function primerEntero(mixed $v): ?int
    {
        return $this->listaEnteros($v)[0] ?? null;
    }

    /** @return array<int,array{0:?Carbon,1:string}> */
    private function listaFechas(mixed $v): array
    {
        if ($v === null || $v === '') {
            return [];
        }
        if (is_string($v) && preg_match('/[a-z]/i', $v)) {
            return array_map(fn ($p) => $this->fechaDe(trim($p)), preg_split('/\s*\/\s*|\s+y\s+/iu', $v));
        }
        return [$this->fechaDe($v)];
    }

    private function elegir(array $lista, int $i, int $n): ?array
    {
        if (count($lista) === $n) {
            return $lista[$i];
        }
        return count($lista) === 1 ? $lista[0] : null;
    }

    /** @return array{0:?Carbon,1:string} fecha y precisión (dia|mes|anio) */
    private function fechaDe(mixed $v): array
    {
        if ($v instanceof DateTimeInterface) {
            return [Carbon::instance($v)->startOfDay(), 'dia'];
        }
        if ($v === null || $v === '') {
            return [null, 'dia'];
        }
        if (is_numeric($v) && (int) $v >= 1990 && (int) $v <= 2100) {
            return [Carbon::create((int) $v, 1, 1)->startOfDay(), 'anio'];
        }
        $t = strtolower(Str::ascii(trim((string) $v)));
        if (preg_match('/^(\d{4})$/', $t, $m)) {
            return [Carbon::create((int) $m[1], 1, 1), 'anio'];
        }
        if (preg_match('/^(\d{1,2})[-\/\s]+([a-z]{3,})\.?[-\/\s]+(\d{4})$/', $t, $m) && isset(self::MESES[substr($m[2], 0, 3)])) {
            return [Carbon::create((int) $m[3], self::MESES[substr($m[2], 0, 3)], (int) $m[1]), 'dia'];
        }
        if (preg_match('/^([a-z]{3,})\.?\s*[-\/]?\s*(\d{4})$/', $t, $m) && isset(self::MESES[substr($m[1], 0, 3)])) {
            return [Carbon::create((int) $m[2], self::MESES[substr($m[1], 0, 3)], 1), 'mes'];
        }
        if (preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/', $t, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            return [Carbon::create((int) $m[3], (int) $m[2], (int) $m[1]), 'dia'];
        }
        return [null, 'dia'];
    }

    private function valor(Worksheet $hoja, int $col, int $fila): mixed
    {
        $celda = $hoja->getCell([$col, $fila]);
        $v = $celda->getValue();
        if ($v === null) {
            return null;
        }
        if (is_numeric($v) && XlDate::isDateTime($celda)) {
            return XlDate::excelToDateTimeObject($v);
        }
        return is_string($v) ? trim(preg_replace('/\s+/u', ' ', $v)) : $v;
    }

    private function filaDe(Worksheet $hoja, string $texto): ?int
    {
        for ($r = 1; $r <= min(30, $hoja->getHighestDataRow()); $r++) {
            $v = $this->valor($hoja, 1, $r);
            if (is_string($v) && $this->clave($v) === strtoupper($texto)) {
                return $r;
            }
        }
        return null;
    }

    private function notasSueltas(Worksheet $hoja): array
    {
        $notas = [];
        for ($r = 1; $r <= $hoja->getHighestDataRow(); $r++) {
            for ($c = 8; $c <= 20; $c++) {
                $v = $this->valor($hoja, $c, $r);
                if (is_string($v) && $v !== '') {
                    $notas[$v] = $v;
                }
            }
        }
        return array_values($notas);
    }

    private function codigo(string $prefijo, ?string $nave, ?Carbon $siembra, string $prec): string
    {
        // Misma regla que el formulario; aquí se agrega el control de duplicados en memoria (modo simulación).
        $base = CodigoPlantacion::base($prefijo, $nave, $siembra, $prec);
        $codigo = $base;
        $i = 2;
        while (isset($this->codigosUsados[$codigo])) {
            $codigo = "$base-$i";
            $i++;
        }
        return $this->codigosUsados[$codigo] = $codigo;
    }

    private function clave(string $s): string
    {
        return strtoupper(trim(Str::ascii(preg_replace('/\s+/u', ' ', $s))));
    }

    private function bonito(string $s): string
    {
        $limpio = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $s)));
        $k = strtolower(Str::ascii($limpio));
        return self::NOMBRES[$k] ?? mb_strtoupper(mb_substr($limpio, 0, 1)) . mb_substr($limpio, 1);
    }

    private function detectarVariantesDeInsumos(): void
    {
        $nombres = Insumo::orderBy('nombre')->pluck('nombre')->all();
        for ($i = 0; $i < count($nombres); $i++) {
            for ($j = $i + 1; $j < count($nombres); $j++) {
                if (levenshtein(strtolower($nombres[$i]), strtolower($nombres[$j])) <= 2) {
                    $this->nota('advertencia', 'Catálogo', 'insumos', "Posibles variantes del mismo producto: \"{$nombres[$i]}\" y \"{$nombres[$j]}\" (confirmar con Amanecer).");
                }
            }
        }
    }

    private function nota(string $nivel, string $hoja, string $ref, string $mensaje): void
    {
        $this->reporte[] = compact('nivel', 'hoja', 'ref', 'mensaje');
    }

    private function contar(string $clave): void
    {
        $this->contadores[$clave] = ($this->contadores[$clave] ?? 0) + 1;
    }
}
