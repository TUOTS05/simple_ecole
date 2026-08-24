<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

/**
 * Cloisonnement multi-écoles : vérifie que l'inscription appartient à l'école
 * de l'utilisateur, pas une permission CRUD complète (le rôle est déjà
 * filtré par le middleware de route).
 */
class EnrollmentPolicy
{
    public function view(User $user, Enrollment $enrollment): bool
    {
        return $user->school_id !== null && $user->school_id === $enrollment->school_id;
    }
}
