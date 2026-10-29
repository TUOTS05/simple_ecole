<?php

namespace App\Support;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Règles de validation cloisonnées par école. Un simple « exists:table,id » accepte l'identifiant
 * d'une autre école de la plateforme : ces règles exigent qu'il appartienne à l'école de
 * l'utilisateur connecté.
 */
class SchoolRules
{
    /**
     * L'identifiant doit exister dans la table et appartenir à l'école de l'utilisateur connecté.
     */
    public static function exists(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)->where('school_id', auth()->user()?->school_id);
    }
}
