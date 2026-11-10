<?php

namespace App\Policies;

use App\Models\LaborCultural;
use App\Models\User;

/** Editar una labor: coordinador/a (Gate::before) y responsable. Docente y estudiante solo consultan (D-03). */
class LaborCulturalPolicy
{
    public function update(User $user, LaborCultural $labor): bool
    {
        return $user->puedeRegistrar();
    }
}
