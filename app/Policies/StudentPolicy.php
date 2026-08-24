<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

/**
 * Cloisonnement multi-écoles : vérifie que l'élève appartient à l'école de
 * l'utilisateur, pas une permission CRUD complète (le rôle est déjà filtré
 * par le middleware de route).
 */
class StudentPolicy
{
    public function view(User $user, Student $student): bool
    {
        return $user->school_id !== null && $user->school_id === $student->school_id;
    }
}
