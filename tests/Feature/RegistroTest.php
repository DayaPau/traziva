<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Rol;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** D-16: registro normal; coordinador/a y responsable con código de verificación y límite de cuentas. */
class RegistroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'traziva.registro.codigos.coordinador' => 'COORD-TEST1',
            'traziva.registro.codigos.responsable' => 'RESP-TEST2',
        ]);
        $this->seed(DatabaseSeeder::class); // 1 coordinador/a y 1 responsable demo activos
    }

    private function datos(string $rol, array $cambios = []): array
    {
        return $cambios + [
            'name' => 'María José Andrade', 'email' => 'maria.andrade@udla.edu.ec',
            'rol_id' => Rol::where('nombre', $rol)->value('id'),
            'password' => 'Clave#2026', 'password_confirmation' => 'Clave#2026',
            'terminos' => '1', 'sitio_web' => '',
        ];
    }

    public function test_pantalla_de_registro_y_enlace_desde_el_login(): void
    {
        $this->get('/login')->assertOk()->assertSee('Crear cuenta')->assertDontSee('Solicitar acceso');
        $this->get('/registro')->assertOk()->assertSee('Código de verificación del rol')->assertSee('Docente / Investigador');
    }

    public function test_estudiante_se_registra_y_entra_de_inmediato(): void
    {
        $this->post('/registro', $this->datos('estudiante'))->assertRedirect('/dashboard');

        $u = User::where('email', 'maria.andrade@udla.edu.ec')->firstOrFail();
        $this->assertSame('activo', $u->estado);
        $this->assertTrue($u->tieneRol('estudiante'));
        $this->assertAuthenticatedAs($u);
        $this->assertTrue(Auditoria::where('accion', 'registro')->where('entidad_id', $u->id)->exists());
        $this->get('/usuarios')->assertForbidden(); // sigue siendo solo consulta
    }

    public function test_docente_no_necesita_codigo(): void
    {
        $this->post('/registro', $this->datos('docente', ['codigo' => '']))->assertSessionHasNoErrors();
        $this->assertTrue(User::where('email', 'maria.andrade@udla.edu.ec')->firstOrFail()->tieneRol('docente'));
    }

    public function test_responsable_necesita_codigo_valido(): void
    {
        $this->post('/registro', $this->datos('responsable'))->assertSessionHasErrors('codigo');
        $this->post('/registro', $this->datos('responsable', ['codigo' => 'COORD-TEST1']))->assertSessionHasErrors('codigo'); // código de otro rol
        $this->assertGuest();

        $this->post('/registro', $this->datos('responsable', ['codigo' => ' RESP-TEST2 ']))->assertRedirect('/dashboard');
        $this->assertTrue(User::where('email', 'maria.andrade@udla.edu.ec')->firstOrFail()->tieneRol('responsable'));
    }

    public function test_limite_de_cuentas_aunque_el_codigo_sea_correcto(): void
    {
        // Ya hay 1 coordinador/a activo/a (límite 1): el código correcto no basta.
        $this->post('/registro', $this->datos('coordinador', ['codigo' => 'COORD-TEST1']))->assertSessionHasErrors('rol_id');

        // Responsables: límite 2; hay 1 demo → entra uno más, el tercero no.
        $this->post('/registro', $this->datos('responsable', ['codigo' => 'RESP-TEST2']))->assertSessionHasNoErrors();
        auth()->logout();
        $this->post('/registro', $this->datos('responsable', ['codigo' => 'RESP-TEST2', 'email' => 'otro@udla.edu.ec']))->assertSessionHasErrors('rol_id');

        $this->assertSame(1, User::whereHas('rol', fn ($q) => $q->where('nombre', 'coordinador'))->count());
        $this->assertSame(2, User::whereHas('rol', fn ($q) => $q->where('nombre', 'responsable'))->count());
    }

    public function test_rol_privilegiado_sin_codigo_configurado_no_se_ofrece(): void
    {
        config(['traziva.registro.codigos.coordinador' => '']);

        $this->get('/registro')->assertDontSee('Coordinador/a de Amanecer');
        $this->post('/registro', $this->datos('coordinador', ['codigo' => '']))->assertSessionHasErrors('rol_id');
    }

    public function test_reglas_de_nombre_correo_y_contrasena(): void
    {
        $casos = [
            'name' => ['name' => 'Ab'],
            'name ' => ['name' => 'Juan123'],
            'email' => ['email' => 'no-es-correo'],
            'email ' => ['email' => 'DOCENTE@TRAZIVA.TEST'], // duplicado sin distinguir mayúsculas
            'password' => ['password' => 'clave2026', 'password_confirmation' => 'clave2026'], // sin mayúscula ni símbolo
            'password ' => ['password' => 'Clave#2026', 'password_confirmation' => 'Otra#2026'], // no coincide
            'terminos' => ['terminos' => null],
            'sitio_web' => ['sitio_web' => 'http://spam.example'], // bot
        ];
        // Aquí se prueban las reglas una por una; el límite de intentos se prueba aparte.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        foreach ($casos as $campo => $cambio) {
            $this->post('/registro', array_merge($this->datos('estudiante'), $cambio))->assertSessionHasErrors(trim($campo));
        }
        $this->assertSame(0, User::where('email', 'maria.andrade@udla.edu.ec')->count());
        $this->assertGuest();
    }

    public function test_limite_de_intentos_contra_fuerza_bruta_del_codigo(): void
    {
        foreach (range(1, 3) as $i) {
            $this->post('/registro', $this->datos('responsable', ['codigo' => "ADIVINA-$i"]))->assertSessionHasErrors('codigo');
        }

        $this->post('/registro', $this->datos('responsable', ['codigo' => 'RESP-TEST2']))
            ->assertSessionHasErrors(['email' => 'Demasiados intentos. Espera 10 min e inténtalo de nuevo.']);
        $this->assertGuest();
    }

    public function test_correo_se_guarda_en_minusculas(): void
    {
        $this->post('/registro', $this->datos('estudiante', ['email' => '  Maria.Andrade@UDLA.edu.ec ']))->assertSessionHasNoErrors();
        $this->assertTrue(User::where('email', 'maria.andrade@udla.edu.ec')->exists());
    }

    // --- Límites también en "Usuarios y roles" ------------------------------------------

    public function test_coordinador_no_puede_crear_ni_ascender_a_un_segundo_coordinador(): void
    {
        $coord = User::where('email', 'coordinador@traziva.test')->firstOrFail();
        $doc = User::where('email', 'docente@traziva.test')->firstOrFail();
        $idCoord = Rol::where('nombre', 'coordinador')->value('id');

        $this->actingAs($coord)->post('/usuarios', [
            'name' => 'Segunda', 'email' => 'segunda@udla.edu.ec', 'rol_id' => $idCoord,
            'password' => 'clave1234', 'password_confirmation' => 'clave1234',
        ])->assertSessionHasErrors('rol_id');

        $this->actingAs($coord)->put("/usuarios/{$doc->id}", [
            'name' => $doc->name, 'email' => $doc->email, 'rol_id' => $idCoord, 'estado' => 'activo',
        ])->assertSessionHasErrors('rol_id');

        $this->assertTrue($doc->fresh()->tieneRol('docente'));
    }

    // --- Cambio de coordinador/a por consola --------------------------------------------

    public function test_comando_cambia_de_coordinador_y_siempre_queda_uno(): void
    {
        $this->artisan('traziva:cambiar-coordinador', ['--email' => 'coordinadora@udla.edu.ec', '--force' => true])
            ->expectsQuestion('Nombre completo', 'Coordinadora Real')
            ->expectsQuestion('Contraseña (mín. 8, mayúsculas, minúsculas, números y símbolos)', 'Clave#2026')
            ->expectsQuestion('Confirmar contraseña', 'Clave#2026')
            ->assertSuccessful();

        $activos = User::whereHas('rol', fn ($q) => $q->where('nombre', 'coordinador'))->where('estado', 'activo')->pluck('email')->all();
        $this->assertSame(['coordinadora@udla.edu.ec'], $activos);
        $this->assertSame('inactivo', User::where('email', 'coordinador@traziva.test')->value('estado'));
        $this->assertTrue(Auditoria::where('accion', 'cambiar_coordinador')->exists());
    }
}
