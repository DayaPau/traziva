<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Revisa en CADA petición que la cuenta siga habilitada. Si el/la coordinador/a
 * la desactiva o vence su acceso temporal mientras está conectado, se cierra la sesión.
 */
class CuentaVigente
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($motivo = $user->motivoBloqueo())) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => $motivo]);
        }

        return $next($request);
    }
}
