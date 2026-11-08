<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function index()
    {
        return view('usuarios.index', [
            // Pendientes primero para que el/la coordinador/a los vea de inmediato.
            'usuarios' => User::with('rol')->orderByRaw("CASE WHEN estado = 'pendiente' THEN 0 ELSE 1 END")->orderBy('name')->get(),
            'roles' => Rol::orderBy('id')->get()->mapWithKeys(fn (Rol $r) => [$r->id => config("traziva.roles.{$r->nombre}", $r->nombre)]),
        ]);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:8',
            'rol_id' => 'required|exists:roles,id',
            'activo_hasta' => 'nullable|date|after:today',
        ]);
        User::create($d + ['estado' => 'activo']);

        return back()->with('ok', 'Usuario creado.');
    }

    /** Activa (o aprueba, si estaba pendiente) / desactiva una cuenta. */
    public function toggle(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, 'No puedes desactivar tu propio usuario.');

        $antes = $user->estado;
        $user->update(['estado' => $antes === 'activo' ? 'inactivo' : 'activo']);

        \App\Models\Auditoria::create([
            'user_id' => $request->user()->id,
            'accion' => $antes === 'pendiente' ? 'aprobar' : 'modificar',
            'entidad' => 'users',
            'entidad_id' => $user->id,
            'valores_antes' => ['estado' => $antes],
            'valores_despues' => ['estado' => $user->estado],
        ]);

        return back()->with('ok', $antes === 'pendiente' ? "Cuenta de {$user->name} aprobada." : 'Estado actualizado.');
    }
}
