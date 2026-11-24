<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistroRequest;
use App\Models\Rol;
use App\Services\Usuarios\RegistroCuentas;
use Illuminate\Support\Facades\Auth;

/**
 * Registro de cuentas (D-16): la cuenta queda activa y la persona entra de inmediato.
 * Coordinador/a y responsable necesitan el código de verificación de su rol.
 */
class RegistroController extends Controller
{
    public function __construct(private RegistroCuentas $registro) {}

    public function create()
    {
        $roles = $this->registro->rolesDisponibles();

        return view('auth.registro', [
            'roles' => $roles->mapWithKeys(fn (Rol $r) => [$r->id => config("traziva.roles.{$r->nombre}", $r->nombre)]),
            // ids de los roles que exigen código (el formulario muestra el campo solo para ellos)
            'conCodigo' => $roles->filter(fn (Rol $r) => $this->registro->requiereCodigo($r->nombre))->modelKeys(),
        ]);
    }

    public function store(RegistroRequest $request)
    {
        $user = $this->registro->registrar($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('ok', "¡Bienvenido/a, {$user->name}! Tu cuenta de ".mb_strtolower($user->rol_etiqueta).' está lista.');
    }
}
