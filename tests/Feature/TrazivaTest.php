<?php

namespace Tests\Feature;

use App\Models\Aplicacion;
use App\Models\LaborCultural;
use App\Models\Plantacion;
use App\Models\Rol;
use App\Models\User;
use App\Services\Idc\IdcCalculator;
use App\Services\Importacion\ExcelCultivosImporter;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrazivaTest extends TestCase
{
    use RefreshDatabase;

    private function excel(): string
    {
        return base_path('database/data/FORMATO_PRODUCCION_CULTIVOS.xlsx');
    }

    private function usuario(string $rol): User
    {
        $this->seed(DatabaseSeeder::class);
        return User::where('email', "$rol@traziva.test")->firstOrFail();
    }

    public function test_importa_el_excel_de_amanecer(): void
    {
        $res = (new ExcelCultivosImporter())->importar($this->excel());

        $this->assertSame(6, \App\Models\Cultivo::count());
        $this->assertGreaterThanOrEqual(6, Plantacion::count());
        $this->assertGreaterThan(0, LaborCultural::count());
        $this->assertGreaterThan(0, Aplicacion::count());
        // Las fechas de 2016 (anteriores a la siembra) deben quedar rechazadas, no importadas.
        $this->assertTrue(collect($res['reporte'])->contains(fn ($f) => $f['nivel'] === 'rechazada' && str_contains($f['mensaje'], '2016')));
        $this->assertSame(0, LaborCultural::whereYear('fecha', 2016)->count());
    }

    public function test_reimportar_no_duplica(): void
    {
        (new ExcelCultivosImporter())->importar($this->excel());
        $antes = [Plantacion::count(), LaborCultural::count(), Aplicacion::count()];

        (new ExcelCultivosImporter())->importar($this->excel());

        $this->assertSame($antes, [Plantacion::count(), LaborCultural::count(), Aplicacion::count()]);
    }

    public function test_simulacion_no_guarda_nada(): void
    {
        (new ExcelCultivosImporter())->importar($this->excel(), true);

        $this->assertSame(0, Plantacion::count());
    }

    public function test_idc_incompleto_sin_meta_ni_cosechas(): void
    {
        $this->usuario('coordinador');
        (new ExcelCultivosImporter())->importar($this->excel());

        $idc = app(IdcCalculator::class)->calcular(Plantacion::first(), 'parcial');

        $this->assertSame('incompleto', $idc->categoria);
        $this->assertNull($idc->puntaje);
    }

    public function test_estudiante_no_accede_a_usuarios(): void
    {
        $this->actingAs($this->usuario('estudiante'))->get('/usuarios')->assertForbidden();
    }

    public function test_coordinador_accede_a_usuarios_y_dashboard(): void
    {
        $u = $this->usuario('coordinador');
        $this->actingAs($u)->get('/usuarios')->assertOk();
        $this->actingAs($u)->get('/dashboard')->assertOk();
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_auditoria_no_se_puede_modificar(): void
    {
        $a = \App\Models\Auditoria::create(['accion' => 'login', 'entidad' => 'users']);
        $this->expectException(\Throwable::class);
        $a->update(['entidad' => 'x']);
    }
}
