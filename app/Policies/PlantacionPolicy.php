<?php

namespace App\Policies;

use App\Models\Plantacion;
use App\Models\User;

/**
 * Coordinador/a: todo (Gate::before en AppServiceProvider).
 * Responsable: crea y modifica plantaciones y sus registros.
 * Docente y estudiante: solo consultan (decisión D-03 en docs/decisiones.md).
 */
class PlantacionPolicy
{
    public function viewAny(User $user): bool { return true; }

    public function view(User $user, Plantacion $plantacion): bool { return true; }

    public function create(User $user): bool { return $user->puedeRegistrar(); }

    public function update(User $user, Plantacion $plantacion): bool { return $user->puedeRegistrar(); }

    /** Registrar labores, aplicaciones, eventos, cosechas y recalcular el IDC. */
    public function registrar(User $user, Plantacion $plantacion): bool { return $user->puedeRegistrar(); }
}
