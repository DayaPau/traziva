<?php

namespace App\Policies;

use App\Models\Cosecha;
use App\Models\User;

/** Editar una cosecha: coordinador/a (Gate::before) y responsable. Docente y estudiante solo consultan (D-03). */
class CosechaPolicy
{
    public function update(User $user, Cosecha $cosecha): bool
    {
        return $user->puedeRegistrar();
    }
}
