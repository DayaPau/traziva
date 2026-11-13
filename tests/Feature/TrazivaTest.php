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
        public function test_usuario_con_acceso_vencido_pierde_la_sesion(): void
    {
        $u = $this->usuario('estudiante');
        $u->update(['activo_hasta' => today()->subDay()]);

        $this->actingAs($u)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_usuario_desactivado_pierde_la_sesion(): void
    {
        $u = $this->usuario('docente');
        $u->update(['estado' => 'inactivo']);

        $this->actingAs($u)->get('/plantaciones')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_estudiante_requiere_fecha_de_vencimiento(): void
    {
        $coord = $this->usuario('coordinador');
        $rol = Rol::where('nombre', 'estudiante')->value('id');
        $datos = ['name' => 'Est Nuevo', 'email' => 'nuevo@traziva.test', 'password' => 'secreto123', 'rol_id' => $rol];

        $this->actingAs($coord)->post('/usuarios', $datos)->assertSessionHasErrors('activo_hasta');
        $this->assertDatabaseMissing('users', ['email' => 'nuevo@traziva.test']);

        $this->actingAs($coord)->post('/usuarios', $datos + ['activo_hasta' => today()->addMonth()->toDateString()])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'nuevo@traziva.test']);
    }

    public function test_crear_usuario_queda_auditado_sin_contrasena(): void
    {
        $coord = $this->usuario('coordinador');
        $rol = Rol::where('nombre', 'responsable')->value('id');

        $this->actingAs($coord)->post('/usuarios', [
            'name' => 'Resp Nuevo', 'email' => 'resp.nuevo@traziva.test', 'password' => 'secreto123', 'rol_id' => $rol,
        ])->assertSessionHasNoErrors();

        $a = \App\Models\Auditoria::where(['entidad' => 'users', 'accion' => 'crear', 'user_id' => $coord->id])->latest('id')->firstOrFail();
        $this->assertSame('resp.nuevo@traziva.test', $a->valores_despues['email']);
        $this->assertArrayNotHasKey('password', $a->valores_despues);
        $this->assertArrayNotHasKey('remember_token', $a->valores_despues);
    }

    public function test_auditoria_no_se_puede_modificar(): void
    {
        $a = \App\Models\Auditoria::create(['accion' => 'login', 'entidad' => 'users']);
        $this->expectException(\Throwable::class);
        $a->update(['entidad' => 'x']);
    }
}
