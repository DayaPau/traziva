<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Cosecha;
use App\Models\Cultivo;
use App\Models\Insumo;
use App\Models\Nave;
use App\Models\Plantacion;
use App\Models\TipoLabor;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Fase 2: catálogos y plantaciones administrados desde la interfaz (sin Excel). */
class CatalogosYPlantacionesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function usuario(string $rol): User
    {
        return User::where('email', "$rol@traziva.test")->firstOrFail();
    }

    private function datosPlantacion(array $cambios = []): array
    {
        return $cambios + [
            'cultivo_id' => Cultivo::firstOrCreate(['nombre' => 'Frutilla'], ['sigla' => 'FRU'])->id,
            'nave_id' => Nave::firstOrCreate(['numero' => '6'])->id,
            'variedad' => 'Albión',
            'num_camas' => 10,
            'num_plantas' => 400,
            'fecha_siembra' => '2025-09-15',
            'fecha_siembra_precision' => 'dia',
            'fecha_inicio_cosecha_estimada' => '2025-12-15',
            'meta_kg_por_cama' => 12.5,
            'meta_periodo' => 'mensual',
            'estado' => 'activa',
            'etapa' => 'crecimiento',
        ];
    }

    // --- Criterio de aceptación -----------------------------------------------

    public function test_crear_cultivo_lechuga_y_plantacion_sin_excel(): void
    {
        $coord = $this->usuario('coordinador');

        $this->actingAs($coord)->post('/catalogos/cultivos', ['nombre' => 'Lechuga', 'tipo_ciclo' => 'corto'])
            ->assertRedirect('/catalogos/cultivos');
        $this->actingAs($coord)->post('/catalogos/naves', ['numero' => '13'])->assertRedirect();

        $lechuga = Cultivo::where('nombre', 'Lechuga')->firstOrFail();
        $this->assertSame('LEC', \App\Services\Plantaciones\CodigoPlantacion::sigla($lechuga));

        $this->actingAs($coord)->post('/plantaciones', $this->datosPlantacion([
            'cultivo_id' => $lechuga->id, 'nave_id' => Nave::where('numero', '13')->value('id'),
            'fecha_siembra' => '2026-06-01', 'fecha_inicio_cosecha_estimada' => '2026-08-01',
        ]))->assertSessionHasNoErrors()->assertRedirect();

        $p = Plantacion::where('cultivo_id', $lechuga->id)->firstOrFail();
        $this->assertSame('LEC-N13-202606', $p->codigo);
        $this->assertSame($coord->id, $p->created_by);
        $this->assertFalse($p->es_prueba);

        $this->actingAs($coord)->get('/plantaciones')->assertOk()->assertSee('Lechuga')->assertSee('LEC-N13-202606');
        $this->actingAs($coord)->get('/plantaciones?q=lechuga')->assertSee('LEC-N13-202606');
        $this->actingAs($coord)->get("/plantaciones/{$p->id}")->assertOk()->assertSee('Historial');
    }

    // --- Plantaciones: reglas de negocio ---------------------------------------

    public function test_codigo_es_editable_y_unico(): void
    {
        $resp = $this->usuario('responsable');
        $this->actingAs($resp)->post('/plantaciones', $this->datosPlantacion(['codigo' => 'fru-especial']))->assertRedirect();
        $this->assertTrue(Plantacion::where('codigo', 'FRU-ESPECIAL')->exists());

        $this->actingAs($resp)->post('/plantaciones', $this->datosPlantacion(['codigo' => 'FRU-ESPECIAL', 'fecha_siembra' => '2025-10-01']))
            ->assertSessionHasErrors('codigo');
    }

    public function test_no_duplica_cultivo_nave_y_siembra(): void
    {
        $resp = $this->usuario('responsable');
        $this->actingAs($resp)->post('/plantaciones', $this->datosPlantacion())->assertSessionHasNoErrors();
        $this->actingAs($resp)->post('/plantaciones', $this->datosPlantacion())->assertSessionHasErrors('fecha_siembra');
        $this->assertSame(1, Plantacion::count());
    }

    public function test_codigo_automatico_evita_colisiones(): void
    {
        $resp = $this->usuario('responsable');
        $this->actingAs($resp)->post('/plantaciones', $this->datosPlantacion(['fecha_siembra' => '2025-09-01']));
        $this->actingAs($resp)->post('/plantaciones', $this->datosPlantacion(['fecha_siembra' => '2025-09-20']));

        $this->assertEqualsCanonicalizing(['FRU-N06-202509', 'FRU-N06-202509-2'], Plantacion::pluck('codigo')->all());
    }

    public function test_validaciones_de_fechas_y_numeros(): void
    {
        $this->actingAs($this->usuario('responsable'))->post('/plantaciones', $this->datosPlantacion([
            'fecha_siembra' => today()->addDay()->toDateString(),
            'fecha_inicio_cosecha_estimada' => '2020-01-01',
            'num_camas' => 0,
            'num_plantas' => -5,
            'meta_kg_por_cama' => 0,
            'nave_id' => 9999,
        ]))->assertSessionHasErrors(['fecha_siembra', 'num_camas', 'num_plantas', 'meta_kg_por_cama', 'nave_id']);

        $this->actingAs($this->usuario('responsable'))->post('/plantaciones', $this->datosPlantacion([
            'fecha_inicio_cosecha_estimada' => '2025-09-01', // anterior a la siembra (15/09/2025)
        ]))->assertSessionHasErrors('fecha_inicio_cosecha_estimada');

        $this->actingAs($this->usuario('responsable'))->post('/plantaciones', $this->datosPlantacion(['meta_periodo' => null]))
            ->assertSessionHasErrors('meta_periodo');
    }

    public function test_siembra_con_precision_de_mes_se_guarda_el_dia_1(): void
    {
        $this->actingAs($this->usuario('responsable'))
            ->post('/plantaciones', $this->datosPlantacion(['fecha_siembra' => '2025-09-23', 'fecha_siembra_precision' => 'mes']));

        $p = Plantacion::firstOrFail();
        $this->assertSame('2025-09-01', $p->fecha_siembra->toDateString());
    }

    public function test_editar_plantacion_queda_auditado(): void
    {
        $resp = $this->usuario('responsable');
        $this->actingAs($resp)->post('/plantaciones', $this->datosPlantacion());
        $p = Plantacion::firstOrFail();

        $this->actingAs($resp)->put("/plantaciones/{$p->id}", $this->datosPlantacion(['codigo' => $p->codigo, 'etapa' => 'floracion']))
            ->assertRedirect("/plantaciones/{$p->id}");

        $this->assertSame('floracion', $p->fresh()->etapa);
        $this->assertTrue(Auditoria::where('entidad', 'plantaciones')->where('entidad_id', $p->id)->where('accion', 'modificar')->exists());
    }

    public function test_cambiar_estado_desde_el_listado(): void
    {
        $resp = $this->usuario('responsable');
        $this->actingAs($resp)->post('/plantaciones', $this->datosPlantacion());
        $p = Plantacion::firstOrFail();

        $this->actingAs($resp)->patch("/plantaciones/{$p->id}/estado", ['estado' => 'finalizada'])->assertRedirect();
        $this->assertSame('finalizada', $p->fresh()->estado);

        $this->actingAs($resp)->patch("/plantaciones/{$p->id}/estado", ['estado' => 'borrada'])->assertSessionHasErrors('estado');
    }

    public function test_cambio_rapido_de_estado_y_etapa_queda_auditado(): void
    {
        $resp = $this->usuario('responsable');
        $this->actingAs($resp)->post('/plantaciones', $this->datosPlantacion());
        $p = Plantacion::firstOrFail();

        $this->actingAs($resp)->patch("/plantaciones/{$p->id}/estado", ['estado' => 'inactiva', 'etapa' => 'cosecha'])->assertRedirect();

        $p->refresh();
        $this->assertSame(['inactiva', 'cosecha'], [$p->estado, $p->etapa]);
        $a = Auditoria::where('entidad', 'plantaciones')->where('entidad_id', $p->id)->where('accion', 'modificar')->latest('id')->firstOrFail();
        $this->assertSame(['estado' => 'activa', 'etapa' => 'crecimiento'], $a->valores_antes);
        $this->assertSame(['estado' => 'inactiva', 'etapa' => 'cosecha'], $a->valores_despues);
        $this->actingAs($resp)->get("/plantaciones/{$p->id}")->assertSee('inactiva')->assertSee('cosecha');

        $this->actingAs($resp)->patch("/plantaciones/{$p->id}/estado", ['estado' => 'activa', 'etapa' => 'madurez'])->assertSessionHasErrors('etapa');
    }

    public function test_listado_muestra_incompleto_si_no_hay_idc(): void
    {
        $this->actingAs($this->usuario('responsable'))->post('/plantaciones', $this->datosPlantacion());

        $this->actingAs($this->usuario('docente'))->get('/plantaciones')->assertOk()->assertSee('Incompleto');
    }

    public function test_solo_se_elimina_plantacion_sin_registros(): void
    {
        $resp = $this->usuario('responsable');
        $this->actingAs($resp)->post('/plantaciones', $this->datosPlantacion());
        $this->actingAs($resp)->post('/plantaciones', $this->datosPlantacion(['fecha_siembra' => '2025-10-01']));
        [$conCosecha, $vacia] = Plantacion::orderBy('id')->get()->all();
        Cosecha::create(['plantacion_id' => $conCosecha->id, 'fecha_cosecha' => '2026-01-10', 'cantidad_kg' => 3]);

        $this->actingAs($resp)->delete("/plantaciones/{$conCosecha->id}")->assertSessionHasErrors('plantacion');
        $this->assertNotSoftDeleted($conCosecha);

        $this->actingAs($resp)->delete("/plantaciones/{$vacia->id}")->assertRedirect('/plantaciones');
        $this->assertSoftDeleted($vacia);
    }

    public function test_plantacion_importada_sin_fecha_se_puede_editar(): void
    {
        $p = Plantacion::create(['codigo' => 'MOR-SN-SF', 'cultivo_id' => Cultivo::create(['nombre' => 'Mora'])->id]);

        $this->actingAs($this->usuario('responsable'))->put("/plantaciones/{$p->id}", [
            'codigo' => 'MOR-SN-SF', 'cultivo_id' => $p->cultivo_id, 'fecha_siembra_precision' => 'dia', 'estado' => 'activa', 'variedad' => 'Castilla',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Castilla', $p->fresh()->variedad);
    }

    // --- Permisos ----------------------------------------------------------------

    public function test_docente_y_estudiante_no_crean_ni_editan(): void
    {
        $this->actingAs($this->usuario('responsable'))->post('/plantaciones', $this->datosPlantacion());
        $p = Plantacion::firstOrFail();

        foreach (['docente', 'estudiante'] as $rol) {
            $u = $this->usuario($rol);
            $this->actingAs($u)->get('/plantaciones')->assertOk()->assertDontSee('Registrar nueva plantación');
            $this->actingAs($u)->get('/plantaciones/crear')->assertForbidden();
            $this->actingAs($u)->post('/plantaciones', $this->datosPlantacion(['fecha_siembra' => '2025-11-01']))->assertForbidden();
            $this->actingAs($u)->get("/plantaciones/{$p->id}/editar")->assertForbidden();
            $this->actingAs($u)->put("/plantaciones/{$p->id}", $this->datosPlantacion())->assertForbidden();
            $this->actingAs($u)->patch("/plantaciones/{$p->id}/estado", ['estado' => 'inactiva'])->assertForbidden();
            $this->actingAs($u)->delete("/plantaciones/{$p->id}")->assertForbidden();
            $this->actingAs($u)->get('/catalogos/cultivos')->assertForbidden();
            $this->actingAs($u)->post('/catalogos/cultivos', ['nombre' => 'Intruso'])->assertForbidden();
        }
        $this->assertSame(1, Plantacion::count());
        $this->assertFalse(Cultivo::where('nombre', 'Intruso')->exists());
    }

    // --- Catálogos ---------------------------------------------------------------

    public function test_catalogos_se_desactivan_en_vez_de_borrarse(): void
    {
        $resp = $this->usuario('responsable');
        $insumo = Insumo::create(['nombre' => 'Fertilizante X', 'categoria' => 'fertilizante']);

        $this->actingAs($resp)->patch("/catalogos/insumos/{$insumo->id}/estado")->assertRedirect();
        $this->assertFalse($insumo->fresh()->activo);
        $this->assertTrue(Auditoria::where('entidad', 'insumos')->where('accion', 'modificar')->exists());

        // Un cultivo inactivo ya no se acepta en plantaciones nuevas.
        $cultivo = Cultivo::create(['nombre' => 'Frambuesa', 'activo' => false]);
        $this->actingAs($resp)->post('/plantaciones', $this->datosPlantacion(['cultivo_id' => $cultivo->id]))->assertSessionHasErrors('cultivo_id');
    }

    public function test_catalogo_valida_unicidad_y_opciones(): void
    {
        $resp = $this->usuario('responsable');
        Cultivo::create(['nombre' => 'Mora']);

        $this->actingAs($resp)->post('/catalogos/cultivos', ['nombre' => 'Mora'])->assertSessionHasErrors('nombre');
        $this->actingAs($resp)->post('/catalogos/cultivos', ['nombre' => 'Uvilla', 'tipo_ciclo' => 'eterno'])->assertSessionHasErrors('tipo_ciclo');
        $this->actingAs($resp)->post('/catalogos/insumos', ['nombre' => 'Y', 'categoria' => 'magia'])->assertSessionHasErrors('categoria');
        $this->actingAs($resp)->get('/catalogos/no-existe')->assertNotFound();
    }

    public function test_tipo_de_labor_se_relaciona_con_cultivos(): void
    {
        $resp = $this->usuario('responsable');
        $mora = Cultivo::create(['nombre' => 'Mora']);
        $uvilla = Cultivo::create(['nombre' => 'Uvilla']);

        $this->actingAs($resp)->post('/catalogos/tipos-labor', ['nombre' => 'Poda', 'cultivos' => [$mora->id, $uvilla->id]])->assertRedirect();
        $poda = TipoLabor::where('nombre', 'Poda')->firstOrFail();
        $this->assertEqualsCanonicalizing([$mora->id, $uvilla->id], $poda->cultivos->modelKeys());

        // Editar sin marcar casillas deja la labor sin cultivos asociados.
        $this->actingAs($resp)->put("/catalogos/tipos-labor/{$poda->id}", ['nombre' => 'Poda'])->assertRedirect();
        $this->assertCount(0, $poda->fresh()->cultivos);
    }

    public function test_todas_las_pantallas_nuevas_cargan(): void
    {
        $coord = $this->usuario('coordinador');
        $this->actingAs($coord)->post('/plantaciones', $this->datosPlantacion());
        $p = Plantacion::firstOrFail();

        $this->actingAs($coord)->get('/plantaciones/crear')->assertOk()->assertSee('Registrar plantación')->assertSee('Provisional');
        $this->actingAs($coord)->get("/plantaciones/{$p->id}/editar")->assertOk()->assertSee($p->codigo);
        $this->actingAs($coord)->get("/plantaciones/{$p->id}")->assertOk()
            ->assertSee('Resumen')->assertSee('Eventos fitosanitarios')->assertSee('Sin cosechas registradas');
        $this->actingAs($coord)->get('/plantaciones/sugerir-codigo?cultivo_id='.$p->cultivo_id.'&nave_id='.$p->nave_id.'&fecha_siembra=2026-03-10')
            ->assertOk()->assertJson(['codigo' => 'FRU-N06-202603']);

        foreach (array_keys(\App\Support\Catalogos::todos()) as $slug) {
            $this->actingAs($coord)->get("/catalogos/$slug")->assertOk();
            $this->actingAs($coord)->get("/catalogos/$slug/crear")->assertOk();
        }
        $this->actingAs($coord)->get('/catalogos/cultivos/'.$p->cultivo_id.'/editar')->assertOk()->assertSee('Frutilla');
    }

    public function test_calidades_muestran_aviso_provisional(): void
    {
        $this->actingAs($this->usuario('coordinador'))->get('/catalogos/calidades')
            ->assertOk()->assertSee('Provisional')->assertSee('Aceptable');
    }
}
