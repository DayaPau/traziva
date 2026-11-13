<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'activo_desde' => 'nullable|date',
            // El estudiante siempre tiene acceso temporal: la fecha de vencimiento es obligatoria.
            'activo_hasta' => array_filter([
                'nullable', 'date', 'after:today',
                $request->filled('activo_desde') ? 'after_or_equal:activo_desde' : null,
                Rule::requiredIf(fn () => Rol::whereKey($request->input('rol_id'))->value('nombre') === 'estudiante'),
            ]),
        ], [
            'activo_hasta.required' => 'El estudiante necesita fecha de vencimiento (acceso temporal).',
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