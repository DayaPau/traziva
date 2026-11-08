<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function index()
    {
        return view('usuarios.index', ['usuarios' => User::with('rol')->orderBy('name')->get(), 'roles' => Rol::orderBy('id')->get()]);
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

    public function toggle(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, 'No puedes desactivar tu propio usuario.');
        $user->update(['estado' => $user->estado === 'activo' ? 'inactivo' : 'activo']);

        return back()->with('ok', 'Estado actualizado.');
    }
}
