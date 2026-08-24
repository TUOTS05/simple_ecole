<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

/**
 * Cloisonnement multi-écoles : vérifie que le paiement appartient à l'école
 * de l'utilisateur, pas une permission CRUD complète (le rôle est déjà
 * filtré par le middleware de route).
 */
class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        return $user->school_id !== null && $user->school_id === $payment->school_id;
    }
}
