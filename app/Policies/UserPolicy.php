<?php

namespace App\Policies;

use App\Models\User;

/**
 * Gestión de usuarios: exclusiva del/la coordinador/a, a quien Gate::before
 * concede todo. Para cualquier otro rol, todas las acciones se niegan.
 */
class UserPolicy
{
    public function viewAny(User $user): bool { return false; }

    public function create(User $user): bool { return false; }

    public function update(User $user, User $modelo): bool { return false; }
}
