<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Rol;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** S5-04: gestión de usuarios y roles (solo coordinador/a, todo auditado). */
class UsuariosTest extends TestCase
{
    use RefreshDatabase;

    private User $coord;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->coord = $this->usuario('coordinador');
    }

    private function usuario(string $rol): User
    {
        return User::where('email', "$rol@traziva.test")->firstOrFail();
    }

    private function rol(string $nombre): int
    {
        return Rol::where('nombre', $nombre)->value('id');
    }

    private function datos(User $u, array $cambios = []): array
    {
        return $cambios + [
            'name' => $u->name, 'email' => $u->email, 'rol_id' => $u->rol_id, 'estado' => $u->estado,
            'activo_desde' => $u->activo_desde?->toDateString(), 'activo_hasta' => $u->activo_hasta?->toDateString(),
        ];
    }

    public function test_crear_usuario_con_acceso_temporal(): void
    {
        $this->actingAs($this->coord)->post('/usuarios', [
            'name' => 'Practicante Uno', 'email' => 'practicante@udla.edu.ec', 'rol_id' => $this->rol('estudiante'),
            'activo_hasta' => today()->addMonths(3)->toDateString(), 'password' => 'clave1234', 'password_confirmation' => 'clave1234',
        ])->assertRedirect('/usuarios');

        $u = User::where('email', 'practicante@udla.edu.ec')->firstOrFail();
        $this->assertSame('activo', $u->estado);
        $this->assertTrue($u->estaVigente());
        $this->assertTrue(Auditoria::where('entidad', 'users')->where('entidad_id', $u->id)->where('accion', 'crear')->exists());
    }

    public function test_la_auditoria_nunca_guarda_la_contrasena(): void
    {
        $this->actingAs($this->coord)->post('/usuarios', [
            'name' => 'Otro', 'email' => 'otro@udla.edu.ec', 'rol_id' => $this->rol('docente'),
            'password' => 'clave1234', 'password_confirmation' => 'clave1234',
        ]);
        $u = User::where('email', 'otro@udla.edu.ec')->firstOrFail();
        $this->actingAs($this->coord)->put("/usuarios/{$u->id}/clave", ['password' => 'nueva5678', 'password_confirmation' => 'nueva5678']);

        foreach (Auditoria::where('entidad', 'users')->where('entidad_id', $u->id)->get() as $a) {
            $this->assertArrayNotHasKey('password', $a->valores_antes ?? []);
            $this->assertArrayNotHasKey('password', $a->valores_despues ?? []);
            $this->assertArrayNotHasKey('remember_token', $a->valores_despues ?? []);
        }
    }

    public function test_editar_rol_y_vigencia_queda_auditado(): void
    {
        $doc = $this->usuario('docente');

        $this->actingAs($this->coord)->put("/usuarios/{$doc->id}", $this->datos($doc, [
            'rol_id' => $this->rol('responsable'), 'activo_hasta' => today()->addYear()->toDateString(),
        ]))->assertRedirect('/usuarios');

        $doc->refresh();
        $this->assertTrue($doc->tieneRol('responsable'));
        $a = Auditoria::where('entidad', 'users')->where('entidad_id', $doc->id)->where('accion', 'modificar')->latest('id')->firstOrFail();
        $this->assertEquals($this->rol('responsable'), $a->valores_despues['rol_id']);
    }

    public function test_aprobar_pendiente_cambiando_el_rol_solicitado(): void
    {
        $p = User::create(['name' => 'Pide Coord', 'email' => 'pide@x.ec', 'password' => 'clave1234', 'rol_id' => $this->rol('coordinador'), 'estado' => 'pendiente']);

        $this->actingAs($this->coord)->put("/usuarios/{$p->id}", $this->datos($p, ['rol_id' => $this->rol('estudiante'), 'estado' => 'activo']))
            ->assertSessionHasNoErrors();

        $p->refresh();
        $this->assertSame('activo', $p->estado);
        $this->assertTrue($p->tieneRol('estudiante'));
        $this->assertTrue(Auditoria::where('accion', 'aprobar')->where('entidad_id', $p->id)->exists());
    }

    public function test_restablecer_contrasena(): void
    {
        $est = $this->usuario('estudiante');

        $this->actingAs($this->coord)->put("/usuarios/{$est->id}/clave", ['password' => 'nueva5678', 'password_confirmation' => 'nueva5678'])
            ->assertRedirect("/usuarios/{$est->id}/editar");

        $this->assertTrue(Hash::check('nueva5678', $est->fresh()->password));
        $this->assertTrue(Auditoria::where('accion', 'restablecer_clave')->where('entidad_id', $est->id)->exists());

        $this->actingAs($this->coord)->put("/usuarios/{$est->id}/clave", ['password' => 'corta', 'password_confirmation' => 'corta'])
            ->assertSessionHasErrors('password');
    }

    public function test_el_coordinador_no_puede_desactivarse_ni_quitarse_el_rol(): void
    {
        $this->actingAs($this->coord)->patch("/usuarios/{$this->coord->id}/estado")->assertSessionHasErrors('estado');
        $this->actingAs($this->coord)->put("/usuarios/{$this->coord->id}", $this->datos($this->coord, ['rol_id' => $this->rol('docente')]))
            ->assertSessionHasErrors('estado');

        $this->assertTrue($this->coord->fresh()->esCoordinador());
        $this->assertSame('activo', $this->coord->fresh()->estado);
    }

    public function test_siempre_queda_un_coordinador_activo(): void
    {
        $otro = User::create(['name' => 'Coord 2', 'email' => 'c2@x.ec', 'password' => 'clave1234', 'rol_id' => $this->rol('coordinador'), 'estado' => 'activo']);

        // Con dos coordinadores, uno puede desactivar al otro…
        $this->actingAs($this->coord)->patch("/usuarios/{$otro->id}/estado")->assertSessionHasNoErrors();
        $this->assertSame('inactivo', $otro->fresh()->estado);

        // …pero el servicio nunca deja al sistema sin coordinador/a activo/a, aunque lo invoque otro actor (p. ej. consola).
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\App\Services\Usuarios\GestionUsuarios::class)
            ->actualizar($this->coord, ['estado' => 'inactivo'], $this->usuario('responsable'));
    }

    public function test_validaciones_del_formulario(): void
    {
        $this->actingAs($this->coord)->post('/usuarios', [
            'name' => '', 'email' => 'docente@traziva.test', 'rol_id' => 999,
            'activo_hasta' => today()->subDay()->toDateString(), 'password' => 'abc', 'password_confirmation' => 'xyz',
        ])->assertSessionHasErrors(['name', 'email', 'rol_id', 'activo_hasta', 'password']);

        $doc = $this->usuario('docente');
        $this->actingAs($this->coord)->put("/usuarios/{$doc->id}", $this->datos($doc, ['estado' => 'pendiente']))
            ->assertSessionHasErrors('estado'); // no se puede volver a "pendiente"
    }

    public function test_solo_el_coordinador_gestiona_usuarios(): void
    {
        $doc = $this->usuario('docente');
        foreach (['responsable', 'docente', 'estudiante'] as $rol) {
            $u = $this->usuario($rol);
            $this->actingAs($u)->get('/usuarios/crear')->assertForbidden();
            $this->actingAs($u)->get("/usuarios/{$doc->id}/editar")->assertForbidden();
            $this->actingAs($u)->put("/usuarios/{$doc->id}", $this->datos($doc, ['rol_id' => $this->rol('coordinador')]))->assertForbidden();
            $this->actingAs($u)->put("/usuarios/{$doc->id}/clave", ['password' => 'nueva5678', 'password_confirmation' => 'nueva5678'])->assertForbidden();
        }
        $this->assertTrue($doc->fresh()->tieneRol('docente'));
    }

    public function test_pantallas_de_usuarios_cargan_y_filtran(): void
    {
        $this->actingAs($this->coord)->get('/usuarios?estado=activo&q=docente')->assertOk()->assertSee('docente@traziva.test')->assertDontSee('estudiante@traziva.test');
        $this->actingAs($this->coord)->get('/usuarios/crear')->assertOk()->assertSee('Contraseña inicial');
        $this->actingAs($this->coord)->get("/usuarios/{$this->coord->id}/editar")->assertOk()->assertSee('tu propia cuenta');
    }
}
