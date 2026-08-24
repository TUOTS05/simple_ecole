<?php

namespace App\Policies;

use App\Models\SchoolClass;
use App\Models\User;

/**
 * Cloisonnement multi-écoles : vérifie que la classe appartient à l'école de
 * l'utilisateur, pas une permission CRUD complète (le rôle est déjà filtré
 * par le middleware de route).
 */
class SchoolClassPolicy
{
    public function view(User $user, SchoolClass $schoolClass): bool
    {
        return $user->school_id !== null && $user->school_id === $schoolClass->school_id;
    }
}
