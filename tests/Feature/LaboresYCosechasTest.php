<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\CategoriaCalidad;
use App\Models\Cosecha;
use App\Models\Cultivo;
use App\Models\LaborCultural;
use App\Models\Nave;
use App\Models\Plantacion;
use App\Models\TipoLabor;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Entregables 6 y 7: labores culturales y cosechas desde la interfaz. */
class LaboresYCosechasTest extends TestCase
{
    use RefreshDatabase;

    private Plantacion $p;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->p = Plantacion::create([
            'codigo' => 'FRU-N06-202509', 'cultivo_id' => Cultivo::create(['nombre' => 'Frutilla'])->id,
            'nave_id' => Nave::create(['numero' => '6'])->id, 'fecha_siembra' => '2025-09-01',
            'num_camas' => 10, 'meta_kg_por_cama' => 5, 'meta_periodo' => 'mensual',
        ]);
        TipoLabor::create(['nombre' => 'Deshierbe']);
    }

    private function usuario(string $rol): User
    {
        return User::where('email', "$rol@traziva.test")->firstOrFail();
    }

    // --- Labores -----------------------------------------------------------------

    public function test_responsable_registra_labor_y_queda_auditada(): void
    {
        $tipo = TipoLabor::firstOrFail();

        $this->actingAs($this->usuario('responsable'))->post("/plantaciones/{$this->p->id}/labores", [
            'tipo_labor_id' => $tipo->id, 'fecha' => today()->subDay()->toDateString(), 'descripcion' => 'Camas 1 a 4',
        ])->assertRedirect("/plantaciones/{$this->p->id}#labores");

        $l = LaborCultural::firstOrFail();
        $this->assertSame('Camas 1 a 4', $l->descripcion);
        $this->assertSame($this->usuario('responsable')->id, $l->registrado_por);
        $this->assertTrue(Auditoria::where('entidad', 'labores_culturales')->where('accion', 'crear')->exists());

        $this->actingAs($this->usuario('docente'))->get("/plantaciones/{$this->p->id}")->assertSee('Camas 1 a 4');
        $this->actingAs($this->usuario('estudiante'))->get('/labores')->assertOk()->assertSee('Deshierbe')->assertDontSee('/editar');
    }

    public function test_labor_valida_fechas_y_tipo(): void
    {
        $resp = $this->usuario('responsable');
        $url = "/plantaciones/{$this->p->id}/labores";

        $this->actingAs($resp)->post($url, ['tipo_labor_id' => TipoLabor::first()->id, 'fecha' => today()->addDay()->toDateString()])
            ->assertSessionHasErrors('fecha');
        $this->actingAs($resp)->post($url, ['tipo_labor_id' => TipoLabor::first()->id, 'fecha' => '2025-08-15']) // antes de la siembra
            ->assertSessionHasErrors('fecha');
        $inactivo = TipoLabor::create(['nombre' => 'Viejo', 'activo' => false]);
        $this->actingAs($resp)->post($url, ['tipo_labor_id' => $inactivo->id, 'fecha' => '2025-10-01'])
            ->assertSessionHasErrors('tipo_labor_id')->assertRedirect("/plantaciones/{$this->p->id}#labores");

        $this->assertSame(0, LaborCultural::count());
    }

    public function test_labor_solo_ofrece_tipos_del_cultivo_si_los_tiene(): void
    {
        $poda = TipoLabor::create(['nombre' => 'Poda']);
        $poda->cultivos()->attach($this->p->cultivo_id);

        $this->actingAs($this->usuario('responsable'))->post("/plantaciones/{$this->p->id}/labores", [
            'tipo_labor_id' => TipoLabor::where('nombre', 'Deshierbe')->value('id'), 'fecha' => '2025-10-01',
        ])->assertSessionHasErrors('tipo_labor_id');
    }

    public function test_editar_labor_queda_auditado(): void
    {
        $l = $this->p->labores()->create(['tipo_labor_id' => TipoLabor::first()->id, 'fecha' => '2025-10-01']);

        $this->actingAs($this->usuario('responsable'))->get("/labores/{$l->id}/editar")->assertOk();
        $this->actingAs($this->usuario('responsable'))->put("/labores/{$l->id}", [
            'tipo_labor_id' => $l->tipo_labor_id, 'fecha' => '2025-10-02', 'descripcion' => 'Corregida',
        ])->assertRedirect();

        $this->assertSame('2025-10-02', $l->fresh()->fecha->toDateString());
        $this->assertTrue(Auditoria::where('entidad', 'labores_culturales')->where('entidad_id', $l->id)->where('accion', 'modificar')->exists());
    }

    // --- Cosechas ------------------------------------------------------------------

    public function test_cosecha_calcula_aprovechable_y_recalcula_idc(): void
    {
        $calidad = CategoriaCalidad::where('nombre', 'Buena')->value('id');

        $this->actingAs($this->usuario('responsable'))->post("/plantaciones/{$this->p->id}/cosechas", [
            'fecha_cosecha' => today()->subDays(3)->toDateString(), 'cantidad_kg' => 12.5, 'merma_kg' => 2.5, 'categoria_calidad_id' => $calidad,
        ])->assertRedirect("/plantaciones/{$this->p->id}#cosechas");

        $c = Cosecha::firstOrFail();
        $this->assertEquals(10.0, $c->fresh()->cantidad_aprovechable_kg);
        $idc = $this->p->ultimoIdc()->first();
        $this->assertNotNull($idc->puntaje, 'Con meta, camas, cosecha y calidad el IDC debe tener puntaje');
        $this->assertTrue(Auditoria::where('entidad', 'idc_calculos')->where('accion', 'crear')->exists());

        $this->actingAs($this->usuario('docente'))->get("/plantaciones/{$this->p->id}")->assertSee('10,00 kg');
    }

    public function test_cosecha_sin_merma_guarda_cero(): void
    {
        $this->actingAs($this->usuario('responsable'))->post("/plantaciones/{$this->p->id}/cosechas", [
            'fecha_cosecha' => today()->toDateString(), 'cantidad_kg' => 4,
        ])->assertSessionHasNoErrors();

        $this->assertEquals(0.0, Cosecha::firstOrFail()->merma_kg);
    }

    public function test_cosecha_valida_merma_fechas_y_cantidad(): void
    {
        $url = "/plantaciones/{$this->p->id}/cosechas";
        $resp = $this->usuario('responsable');

        $this->actingAs($resp)->post($url, ['fecha_cosecha' => today()->toDateString(), 'cantidad_kg' => 5, 'merma_kg' => 6])
            ->assertSessionHasErrors('merma_kg');
        $this->actingAs($resp)->post($url, ['fecha_cosecha' => today()->addDay()->toDateString(), 'cantidad_kg' => 0])
            ->assertSessionHasErrors(['fecha_cosecha', 'cantidad_kg']);
        $this->actingAs($resp)->post($url, ['fecha_cosecha' => '2025-08-01', 'cantidad_kg' => 3]) // antes de la siembra
            ->assertSessionHasErrors('fecha_cosecha');

        $this->assertSame(0, Cosecha::count());
    }

    public function test_editar_cosecha_recalcula_idc_y_audita(): void
    {
        $c = Cosecha::create(['plantacion_id' => $this->p->id, 'fecha_cosecha' => today()->subDay(), 'cantidad_kg' => 10, 'merma_kg' => 1,
            'categoria_calidad_id' => CategoriaCalidad::where('nombre', 'Buena')->value('id')]);
        $calculosAntes = $this->p->idcCalculos()->count();

        $this->actingAs($this->usuario('responsable'))->get("/cosechas/{$c->id}/editar")->assertOk()->assertSee('Aprovechable');
        $this->actingAs($this->usuario('responsable'))->put("/cosechas/{$c->id}", [
            'fecha_cosecha' => today()->subDay()->toDateString(), 'cantidad_kg' => 20, 'merma_kg' => 1, 'categoria_calidad_id' => $c->categoria_calidad_id,
        ])->assertRedirect();

        $this->assertEquals(19.0, $c->fresh()->cantidad_aprovechable_kg);
        $this->assertGreaterThan($calculosAntes, $this->p->idcCalculos()->count());
        $this->assertTrue(Auditoria::where('entidad', 'cosechas')->where('entidad_id', $c->id)->where('accion', 'modificar')->exists());
    }

    public function test_listado_de_cosechas_muestra_totales(): void
    {
        Cosecha::create(['plantacion_id' => $this->p->id, 'fecha_cosecha' => today()->subDay(), 'cantidad_kg' => 10, 'merma_kg' => 2]);
        Cosecha::create(['plantacion_id' => $this->p->id, 'fecha_cosecha' => today(), 'cantidad_kg' => 5, 'merma_kg' => 0]);

        $this->actingAs($this->usuario('estudiante'))->get('/cosechas')->assertOk()
            ->assertSee('15,00 kg')->assertSee('13,00 kg')->assertDontSee('/editar');
    }

    // --- Permisos (D-03: docente y estudiante solo consultan) -------------------------

    public function test_docente_y_estudiante_no_registran_ni_editan(): void
    {
        $l = $this->p->labores()->create(['tipo_labor_id' => TipoLabor::first()->id, 'fecha' => '2025-10-01']);
        $c = Cosecha::create(['plantacion_id' => $this->p->id, 'fecha_cosecha' => today(), 'cantidad_kg' => 3]);

        foreach (['docente', 'estudiante'] as $rol) {
            $u = $this->usuario($rol);
            $this->actingAs($u)->get("/plantaciones/{$this->p->id}")->assertOk()->assertDontSee('Registrar labor cultural')->assertDontSee('Registrar cosecha');
            $this->actingAs($u)->post("/plantaciones/{$this->p->id}/labores", ['tipo_labor_id' => $l->tipo_labor_id, 'fecha' => '2025-10-05'])->assertForbidden();
            $this->actingAs($u)->get("/labores/{$l->id}/editar")->assertForbidden();
            $this->actingAs($u)->put("/labores/{$l->id}", ['tipo_labor_id' => $l->tipo_labor_id, 'fecha' => '2025-10-05'])->assertForbidden();
            $this->actingAs($u)->post("/plantaciones/{$this->p->id}/cosechas", ['fecha_cosecha' => today()->toDateString(), 'cantidad_kg' => 1])->assertForbidden();
            $this->actingAs($u)->get("/cosechas/{$c->id}/editar")->assertForbidden();
            $this->actingAs($u)->put("/cosechas/{$c->id}", ['fecha_cosecha' => today()->toDateString(), 'cantidad_kg' => 99])->assertForbidden();
            $this->actingAs($u)->post("/plantaciones/{$this->p->id}/idc")->assertForbidden();
        }
        $this->assertSame(1, LaborCultural::count());
        $this->assertEquals(3.0, $c->fresh()->cantidad_kg);
    }
}
