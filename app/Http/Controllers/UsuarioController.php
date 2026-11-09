<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClaveRequest;
use App\Http\Requests\UsuarioRequest;
use App\Models\Rol;
use App\Models\User;
use App\Services\Usuarios\GestionUsuarios;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Usuarios y roles (solo coordinador/a). Las reglas de negocio viven en GestionUsuarios. */
class UsuarioController extends Controller
{
    public function __construct(private GestionUsuarios $gestion) {}

    public function index(Request $request)
    {
        $f = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'rol_id' => ['nullable', 'integer'],
            'estado' => ['nullable', Rule::in(array_keys(User::ESTADOS))],
        ]);

        $usuarios = User::with('rol')
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('name', 'like', "%$t%")->orWhere('email', 'like', "%$t%")))
            ->when($f['rol_id'] ?? null, fn ($q, $r) => $q->where('rol_id', $r))
            ->when($f['estado'] ?? null, fn ($q, $e) => $q->where('estado', $e))
            // Pendientes primero para que el/la coordinador/a los vea de inmediato.
            ->orderByRaw("CASE WHEN estado = 'pendiente' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();

        return view('usuarios.index', [
            'usuarios' => $usuarios,
            'roles' => $this->roles(),
            'pendientes' => User::where('estado', 'pendiente')->count(),
        ]);
    }

    public function create()
    {
        return view('usuarios.form', ['u' => new User(['estado' => 'activo']), 'roles' => $this->roles()]);
    }

    public function store(UsuarioRequest $request)
    {
        $u = $this->gestion->crear($request->validated());

        return redirect()->route('usuarios.index')->with('ok', "Usuario {$u->name} creado.");
    }

    public function edit(User $user)
    {
        return view('usuarios.form', ['u' => $user, 'roles' => $this->roles()]);
    }

    public function update(UsuarioRequest $request, User $user)
    {
        $this->gestion->actualizar($user, $request->validated(), $request->user());

        return redirect()->route('usuarios.index')->with('ok', "Cambios de {$user->name} guardados.");
    }

    /** Aprobar (pendiente → activo), activar o desactivar desde el listado. */
    public function toggle(Request $request, User $user)
    {
        $antes = $user->estado;
        $this->gestion->alternarEstado($user, $request->user());

        return back()->with('ok', match ($antes) {
            'pendiente' => "Cuenta de {$user->name} aprobada.",
            'activo' => "Cuenta de {$user->name} desactivada.",
            default => "Cuenta de {$user->name} activada.",
        });
    }

    public function restablecerClave(ClaveRequest $request, User $user)
    {
        $this->gestion->restablecerClave($user, $request->validated('password'));

        return redirect()->route('usuarios.edit', $user)
            ->with('ok', "Contraseña de {$user->name} restablecida. Comunícasela por un canal seguro.");
    }

    private function roles()
    {
        return Rol::orderBy('id')->get()->mapWithKeys(fn (Rol $r) => [$r->id => config("traziva.roles.{$r->nombre}", $r->nombre)]);
    }
}
