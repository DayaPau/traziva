<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $datos = $request->validate(['email' => 'required|email', 'password' => 'required|string']);

        if (! Auth::attempt($datos, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Credenciales incorrectas.'])->onlyInput('email');
        }
        if (! $request->user()->estaVigente()) {
            Auth::logout();
            return back()->withErrors(['email' => 'Tu acceso está inactivo o venció. Contacta al coordinador.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        Auditoria::create(['user_id' => Auth::id(), 'accion' => 'login', 'entidad' => 'users', 'entidad_id' => Auth::id()]);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
