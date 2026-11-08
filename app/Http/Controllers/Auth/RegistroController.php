<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistroRequest;
use App\Models\Auditoria;
use App\Models\Rol;
use App\Models\User;

/**
 * Registro público con aprobación (D-02): la persona elige el rol que solicita,
 * pero la cuenta nace "pendiente" y no puede ingresar hasta que el/la
 * coordinador/a la apruebe en "Usuarios y roles".
 */
class RegistroController extends Controller
{
    public function create()
    {
        $roles = Rol::orderBy('id')->get()
            ->mapWithKeys(fn (Rol $r) => [$r->id => config("traziva.roles.{$r->nombre}", $r->nombre)]);

        return view('auth.registro', ['roles' => $roles]);
    }

    public function store(RegistroRequest $request)
    {
        $user = User::create($request->validated() + ['estado' => 'pendiente']);

        Auditoria::create([
            'accion' => 'registro',
            'entidad' => 'users',
            'entidad_id' => $user->id,
            'valores_despues' => ['name' => $user->name, 'email' => $user->email, 'rol_id' => $user->rol_id, 'estado' => 'pendiente'],
        ]);

        return redirect()->route('login')
            ->with('ok', 'Solicitud enviada. Podrás ingresar cuando el/la coordinador/a apruebe tu cuenta.');
    }
}
