<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'cuenta.vigente' => \App\Http\Middleware\CuentaVigente::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Límite de intentos en login/registro: mensaje claro en el mismo formulario.
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, \Illuminate\Http\Request $request) {
            if ($request->is('login', 'registro')) {
                $minutos = max(1, (int) ceil(($e->getHeaders()['Retry-After'] ?? 60) / 60));

                return back()->withErrors(['email' => "Demasiados intentos. Espera {$minutos} min e inténtalo de nuevo."])->onlyInput('email');
            }
        });
    })->create();
