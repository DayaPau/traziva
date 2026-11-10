<?php

namespace Tests\Feature;

use App\Models\Aplicacion;
use App\Models\Cosecha;
use App\Models\LaborCultural;
use App\Models\Plantacion;
use App\Models\User;
use App\Services\Importacion\ExcelCultivosImporter;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DatosPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Entregable 9: datos reales importados y datos sintéticos de demostración separados. */
class DatosDemostracionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_importador_deja_11_plantaciones_57_labores_y_79_aplicaciones(): void
    {
        (new ExcelCultivosImporter())->importar(base_path('database/data/FORMATO_PRODUCCION_CULTIVOS.xlsx'));

        $this->assertSame(11, Plantacion::count());
        $this->assertSame(57, LaborCultural::count());
        $this->assertSame(79, Aplicacion::count());
        $this->assertSame(0, Plantacion::where('es_prueba', true)->count());
    }

    public function test_seeder_de_prueba_produce_idc_alto_medio_e_incompleto(): void
    {
        $this->seed(DatosPruebaSeeder::class);

        $cat = fn (string $codigo) => Plantacion::where('codigo', $codigo)->firstOrFail()->ultimoIdc()->first();
        $this->assertSame('alto', $cat('TEST-01-ALTO')->categoria);
        $this->assertSame('medio', $cat('TEST-02-MEDIO')->categoria);
        $this->assertSame('incompleto', $cat('TEST-03-INCOMPLETO')->categoria);
        $this->assertNull($cat('TEST-03-INCOMPLETO')->puntaje);
        $this->assertContains('Al menos una cosecha registrada', $cat('TEST-03-INCOMPLETO')->datos_faltantes);

        $this->assertSame(3, Plantacion::where('es_prueba', true)->where('codigo', 'like', 'TEST-%')->count());
    }

    public function test_datos_de_prueba_se_ven_marcados_y_se_pueden_filtrar(): void
    {
        $this->seed(DatosPruebaSeeder::class);
        Plantacion::create(['codigo' => 'MOR-N04-202410', 'cultivo_id' => Plantacion::first()->cultivo_id]);
        $coord = User::where('email', 'coordinador@traziva.test')->firstOrFail();

        $this->actingAs($coord)->get('/plantaciones')->assertSee('TEST-01-ALTO')->assertSee('Prueba');
        $this->actingAs($coord)->get('/plantaciones?datos=reales')->assertSee('MOR-N04-202410')->assertDontSee('TEST-01-ALTO');
        $this->actingAs($coord)->get('/plantaciones?datos=prueba')->assertSee('TEST-02-MEDIO')->assertDontSee('MOR-N04-202410');
    }

    public function test_el_panel_no_suma_datos_de_prueba(): void
    {
        $this->seed(DatosPruebaSeeder::class); // 3 plantaciones de prueba activas, con labores y cosechas
        $coord = User::where('email', 'coordinador@traziva.test')->firstOrFail();

        $res = $this->actingAs($coord)->get('/dashboard')->assertOk()->assertSee('3 plantaciones de');
        $totales = $res->viewData('totales');
        $this->assertSame(0, $totales['Plantaciones activas']);
        $this->assertSame(0, $totales['Cosechas']);
        $this->assertSame(0, $totales['Labores registradas']);
    }

    public function test_resembrar_no_duplica_y_borrar_no_toca_datos_reales(): void
    {
        (new ExcelCultivosImporter())->importar(base_path('database/data/FORMATO_PRODUCCION_CULTIVOS.xlsx'));
        $reales = [Plantacion::count(), LaborCultural::count(), Aplicacion::count()];

        $this->seed(DatosPruebaSeeder::class);
        $this->seed(DatosPruebaSeeder::class); // dos veces: reemplaza, no duplica
        $this->assertSame(3, Plantacion::where('es_prueba', true)->count());

        $this->artisan('traziva:borrar-prueba', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, Plantacion::withTrashed()->where('es_prueba', true)->count());
        $this->assertSame(0, Cosecha::count()); // las cosechas de prueba se borraron en cascada
        $this->assertSame($reales, [Plantacion::count(), LaborCultural::count(), Aplicacion::count()]);
    }
}
