<?php

namespace App\Support;

use App\Models\CategoriaCalidad;
use App\Models\Cultivo;
use App\Models\Insumo;
use App\Models\Nave;
use App\Models\PlagaEnfermedad;
use App\Models\TipoLabor;
use Illuminate\Validation\Rule;

/**
 * Definición de los catálogos administrables desde la interfaz.
 *
 * Los 6 catálogos tienen la misma forma (listar, crear, editar, activar/desactivar),
 * así que se describen aquí una sola vez y los atiende un único CatalogoController
 * con dos vistas. Agregar un campo = agregar una entrada en 'campos'.
 *
 * Tipos de campo: text, number, select (con 'opciones'), relacion (casillas many-to-many).
 */
class Catalogos
{
    public static function todos(): array
    {
        return [
            'cultivos' => [
                'modelo' => Cultivo::class,
                'titulo' => 'Cultivos',
                'singular' => 'cultivo',
                'descripcion' => 'Especies que produce la granja. Tipo de ciclo y vida productiva son opcionales.',
                'conteo' => 'plantaciones',
                'campos' => [
                    'nombre' => ['label' => 'Nombre', 'tipo' => 'text', 'reglas' => ['required', 'string', 'max:80'], 'unico' => true],
                    'sigla' => ['label' => 'Sigla para códigos', 'tipo' => 'text', 'reglas' => ['nullable', 'alpha', 'max:5'],
                        'ayuda' => 'Ej.: FRU → FRU-N06-202509. Si se deja vacía se usan las 3 primeras letras.'],
                    'tipo_ciclo' => ['label' => 'Tipo de ciclo', 'tipo' => 'select', 'reglas' => ['nullable'],
                        'opciones' => ['corto' => 'Corto', 'semiperenne' => 'Semiperenne', 'perenne' => 'Perenne']],
                    'vida_productiva_meses' => ['label' => 'Vida productiva (meses)', 'tipo' => 'number', 'reglas' => ['nullable', 'integer', 'min:1', 'max:600'],
                        'ayuda' => 'Ej.: frutilla 18–24, mora 60–72.'],
                ],
            ],
            'naves' => [
                'modelo' => Nave::class,
                'titulo' => 'Naves',
                'singular' => 'nave',
                'descripcion' => 'Invernaderos o naves de producción, identificados por número.',
                'conteo' => 'plantaciones',
                'campos' => [
                    'numero' => ['label' => 'Número', 'tipo' => 'text', 'reglas' => ['required', 'string', 'max:10'], 'unico' => true],
                    'descripcion' => ['label' => 'Descripción', 'tipo' => 'text', 'reglas' => ['nullable', 'string', 'max:120']],
                ],
            ],
            'tipos-labor' => [
                'modelo' => TipoLabor::class,
                'titulo' => 'Tipos de labor',
                'singular' => 'tipo de labor',
                'descripcion' => 'Labores culturales (poda, deshierbe, riego…) y los cultivos en que se aplican.',
                'conteo' => 'labores',
                'campos' => [
                    'nombre' => ['label' => 'Nombre', 'tipo' => 'text', 'reglas' => ['required', 'string', 'max:80'], 'unico' => true],
                    'cultivos' => ['label' => 'Cultivos en que aplica', 'tipo' => 'relacion', 'modelo' => Cultivo::class, 'texto' => 'nombre',
                        'reglas' => ['nullable', 'array'], 'reglas_item' => ['integer', 'exists:cultivos,id']],
                ],
            ],
            'insumos' => [
                'modelo' => Insumo::class,
                'titulo' => 'Insumos',
                'singular' => 'insumo',
                'descripcion' => 'Fertilizantes, fitosanitarios y enmiendas usados en las aplicaciones.',
                'conteo' => 'detalles',
                'campos' => [
                    'nombre' => ['label' => 'Nombre comercial', 'tipo' => 'text', 'reglas' => ['required', 'string', 'max:100'], 'unico' => true],
                    'categoria' => ['label' => 'Categoría', 'tipo' => 'select', 'reglas' => ['required'],
                        'opciones' => ['fertilizante' => 'Fertilizante', 'fitosanitario' => 'Fitosanitario', 'enmienda_organica' => 'Enmienda orgánica', 'otro' => 'Otro']],
                ],
            ],
            'plagas' => [
                'modelo' => PlagaEnfermedad::class,
                'titulo' => 'Plagas y enfermedades',
                'singular' => 'plaga o enfermedad',
                'descripcion' => 'Problemas fitosanitarios que se registran en eventos y aplicaciones.',
                'conteo' => 'eventos',
                'campos' => [
                    'nombre' => ['label' => 'Nombre', 'tipo' => 'text', 'reglas' => ['required', 'string', 'max:80'], 'unico' => true],
                    'tipo' => ['label' => 'Tipo', 'tipo' => 'select', 'reglas' => ['required'],
                        'opciones' => ['plaga' => 'Plaga', 'enfermedad' => 'Enfermedad', 'otro' => 'Otro']],
                ],
            ],
            'calidades' => [
                'modelo' => CategoriaCalidad::class,
                'titulo' => 'Categorías de calidad',
                'singular' => 'categoría de calidad',
                'descripcion' => 'Clasificación de la cosecha. El orden va de menor (1) a mayor calidad y lo usa el IDC.',
                'provisional' => 'Catálogo provisional: Amanecer aún no confirma las categorías reales.',
                'conteo' => 'cosechas',
                'campos' => [
                    'nombre' => ['label' => 'Nombre', 'tipo' => 'text', 'reglas' => ['required', 'string', 'max:40'], 'unico' => true],
                    'orden' => ['label' => 'Orden (1 = menor calidad)', 'tipo' => 'number', 'reglas' => ['required', 'integer', 'min:1', 'max:20']],
                ],
            ],
        ];
    }

    public static function get(string $slug): array
    {
        return self::todos()[$slug] ?? abort(404);
    }

    /** Reglas de validación del formulario, incluida la unicidad que ignora el registro editado. */
    public static function reglas(string $slug, ?int $ignorarId = null): array
    {
        $def = self::get($slug);
        $tabla = (new $def['modelo'])->getTable();
        $reglas = [];

        foreach ($def['campos'] as $campo => $c) {
            $r = $c['reglas'];
            if ($c['tipo'] === 'select') {
                $r[] = Rule::in(array_keys($c['opciones']));
            }
            if (! empty($c['unico'])) {
                $r[] = Rule::unique($tabla, $campo)->ignore($ignorarId);
            }
            $reglas[$campo] = $r;
            if ($c['tipo'] === 'relacion') {
                $reglas["$campo.*"] = $c['reglas_item'];
            }
        }

        return $reglas;
    }

    /** Etiquetas para los mensajes de validación ("El campo Nombre es obligatorio"). */
    public static function atributos(string $slug): array
    {
        return collect(self::get($slug)['campos'])->map(fn ($c) => mb_strtolower($c['label']))->all();
    }
}
