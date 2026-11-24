<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Cultivo;
use App\Models\Plantacion;
use App\Models\Rol;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Fase 1: autenticación, registro con aprobación, menú por rol y permisos (403). */
class AccesoYPermisosTest extends TestCase
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

    private function plantacion(): Plantacion
    {
        return Plantacion::create(['codigo' => 'TEST-001', 'cultivo_id' => Cultivo::create(['nombre' => 'Frutilla'])->id, 'es_prueba' => true]);
    }

    // --- Menú por rol ---------------------------------------------------------

    public function test_coordinador_ve_usuarios_y_auditoria_en_el_menu(): void
    {
        $this->actingAs($this->usuario('coordinador'))->get('/dashboard')
            ->assertOk()->assertSee('Usuarios y roles')->assertSee('Auditoría básica')->assertSee('Cultivos');
    }

    public function test_roles_no_coordinadores_no_ven_usuarios_ni_auditoria(): void
    {
        foreach (['responsable', 'docente', 'estudiante'] as $rol) {
            $this->actingAs($this->usuario($rol))->get('/dashboard')
                ->assertOk()->assertSee('Cultivos')->assertSee('Reportes CSV')
                ->assertDontSee('Usuarios y roles')->assertDontSee('Auditoría básica');
        }
    }

    // --- 403 en backend -------------------------------------------------------

    public function test_solo_el_coordinador_accede_a_usuarios(): void
    {
        foreach (['responsable', 'docente', 'estudiante'] as $rol) {
            $this->actingAs($this->usuario($rol))->get('/usuarios')->assertForbidden();
            $this->actingAs($this->usuario($rol))->post('/usuarios', [])->assertForbidden();
        }
        $this->actingAs($this->usuario('coordinador'))->get('/usuarios')->assertOk();
    }

    public function test_docente_y_estudiante_solo_consultan(): void
    {
        $p = $this->plantacion();
        $cosecha = ['fecha_cosecha' => today()->toDateString(), 'cantidad_kg' => 5];

        foreach (['docente', 'estudiante'] as $rol) {
            $u = $this->usuario($rol);
            $this->actingAs($u)->get("/plantaciones/{$p->id}")->assertOk()->assertDontSee('Recalcular IDC');
            $this->actingAs($u)->post("/plantaciones/{$p->id}/cosechas", $cosecha)->assertForbidden();
            $this->actingAs($u)->post("/plantaciones/{$p->id}/idc")->assertForbidden();
        }
        $this->assertSame(0, $p->cosechas()->count());
    }

    public function test_responsable_puede_registrar_cosecha(): void
    {
        $p = $this->plantacion();

        $this->actingAs($this->usuario('responsable'))
            ->post("/plantaciones/{$p->id}/cosechas", ['fecha_cosecha' => today()->toDateString(), 'cantidad_kg' => 5])
            ->assertRedirect();

        $this->assertSame(1, $p->cosechas()->count());
    }

    public function test_todos_los_roles_descargan_csv(): void
    {
        foreach (['coordinador', 'responsable', 'docente', 'estudiante'] as $rol) {
            $this->actingAs($this->usuario($rol))->get('/exportar/plantaciones.csv')->assertOk();
        }
    }

    // --- Cuentas pendientes heredadas (antes del registro con código, D-16) --------
    // El registro público ahora se prueba en RegistroTest.

    public function test_cuenta_pendiente_no_puede_ingresar(): void
    {
        User::create(['name' => 'Antigua', 'email' => 'antigua@udla.edu.ec', 'password' => 'clave1234',
            'rol_id' => Rol::where('nombre', 'docente')->value('id'), 'estado' => 'pendiente']);

        $this->post('/login', ['email' => 'antigua@udla.edu.ec', 'password' => 'clave1234'])
            ->assertSessionHasErrors(['email' => 'Tu cuenta está pendiente de aprobación por el/la coordinador/a.']);
        $this->assertGuest();
    }

    public function test_coordinador_aprueba_cuenta_pendiente(): void
    {
        $u = User::create(['name' => 'Pend', 'email' => 'p@x.ec', 'password' => 'clave1234', 'rol_id' => Rol::where('nombre', 'docente')->value('id'), 'estado' => 'pendiente']);

        $this->actingAs($this->usuario('coordinador'))->patch("/usuarios/{$u->id}/estado")->assertRedirect();

        $this->assertSame('activo', $u->fresh()->estado);
        $this->assertTrue(Auditoria::where('accion', 'aprobar')->where('entidad_id', $u->id)->exists());
    }

    // --- Vigencia de la cuenta -------------------------------------------------

    public function test_cuenta_vencida_no_puede_ingresar(): void
    {
        $u = $this->usuario('estudiante');
        $u->update(['password' => 'clave1234', 'activo_hasta' => today()->subDay()]);

        $this->post('/login', ['email' => $u->email, 'password' => 'clave1234'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_cuenta_desactivada_con_sesion_abierta_es_expulsada(): void
    {
        $u = $this->usuario('responsable');
        $this->actingAs($u)->get('/dashboard')->assertOk();

        $u->update(['estado' => 'inactivo']);

        $this->actingAs($u)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_correcto_redirige_al_dashboard(): void
    {
        $u = $this->usuario('docente');
        $u->update(['password' => 'clave1234']);

        $this->post('/login', ['email' => $u->email, 'password' => 'clave1234'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($u);
    }

    public function test_no_existe_registro_para_usuarios_autenticados(): void
    {
        $this->actingAs($this->usuario('docente'))->get('/registro')->assertRedirect();
    }
}
