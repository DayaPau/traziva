<?php

namespace Tests\Feature;

use App\Models\Cultivo;
use App\Models\Plantacion;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DatosPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Sección «Índice de Desempeño»: comparación del IDC de todas las plantaciones. */
class IdcSeccionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatosPruebaSeeder::class); // TEST-01 alto, TEST-02 medio, TEST-03 incompleto
        // Una plantación real sin cálculo de IDC (sin cosechas).
        Plantacion::create(['codigo' => 'MOR-N04-202410', 'cultivo_id' => Cultivo::where('nombre', 'Mora')->value('id')]);
    }

    private function usuario(string $rol): User
    {
        return User::where('email', "$rol@traziva.test")->firstOrFail();
    }

    public function test_todos_los_roles_ven_la_seccion_y_el_menu_la_habilita(): void
    {
        foreach (['coordinador', 'responsable', 'docente', 'estudiante'] as $rol) {
            $this->actingAs($this->usuario($rol))->get('/idc')->assertOk()
                ->assertSee('Índice de Desempeño del Cultivo')->assertSee('fórmula en borrador')
                ->assertSee('TEST-01-ALTO')->assertSee('MOR-N04-202410');
        }
        $this->actingAs($this->usuario('docente'))->get('/dashboard')->assertSee(route('idc.index'));
    }

    public function test_resumen_por_categoria_y_orden_por_puntaje(): void
    {
        $res = $this->actingAs($this->usuario('docente'))->get('/idc');

        $resumen = $res->viewData('resumen');
        $this->assertSame(1, $resumen['alto']);
        $this->assertSame(1, $resumen['medio']);
        $this->assertSame(0, $resumen['bajo']);
        $this->assertSame(2, $resumen['incompleto']); // TEST-03 (faltan datos) y la real sin cálculo

        $codigos = $res->viewData('plantaciones')->pluck('codigo')->all();
        $this->assertSame(['TEST-01-ALTO', 'TEST-02-MEDIO'], array_slice($codigos, 0, 2));
        $res->assertSee('Sin cálculo: faltan cosechas registradas.');
    }

    public function test_filtros_por_categoria_y_origen_de_datos(): void
    {
        $u = $this->usuario('estudiante');

        $this->actingAs($u)->get('/idc?categoria=alto')->assertSee('TEST-01-ALTO')->assertDontSee('TEST-02-MEDIO');
        $this->actingAs($u)->get('/idc?categoria=incompleto')->assertSee('TEST-03-INCOMPLETO')->assertSee('MOR-N04-202410')->assertDontSee('TEST-01-ALTO');
        $this->actingAs($u)->get('/idc?datos=reales')->assertSee('MOR-N04-202410')->assertDontSee('TEST-01-ALTO');
        $this->actingAs($u)->get('/idc?categoria=raro')->assertSessionHasErrors('categoria');
    }

    public function test_recalcular_todos_solo_coordinador_y_responsable(): void
    {
        foreach (['docente', 'estudiante'] as $rol) {
            $this->actingAs($this->usuario($rol))->get('/idc')->assertDontSee(route('idc.recalcular')); // sin botón
            $this->actingAs($this->usuario($rol))->post('/idc/recalcular')->assertForbidden();
        }

        $antes = \App\Models\IdcCalculo::count();
        $this->actingAs($this->usuario('responsable'))->post('/idc/recalcular')->assertRedirect()
            ->assertSessionHas('ok', fn ($m) => str_contains($m, '4 plantaciones activas'));
        $this->assertSame($antes + 4, \App\Models\IdcCalculo::count());

        // La plantación real ahora tiene cálculo explícito y sigue siendo incompleta (sin cosechas).
        $this->assertSame('incompleto', Plantacion::where('codigo', 'MOR-N04-202410')->first()->ultimoIdc()->first()->categoria);
    }
}
