<?php

namespace App\Policies;

use App\Models\StudentInstallment;
use App\Models\User;

/**
 * Cloisonnement multi-écoles : vérifie que l'échéance appartient à l'école de
 * l'utilisateur, pas une permission CRUD complète (le rôle est déjà filtré
 * par le middleware de route).
 */
class StudentInstallmentPolicy
{
    public function view(User $user, StudentInstallment $studentInstallment): bool
    {
        return $user->school_id !== null && $user->school_id === $studentInstallment->school_id;
    }
}
