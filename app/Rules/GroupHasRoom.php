<?php

namespace App\Rules;

use App\Models\SchoolClass;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Refuse un groupe de crèche complet quand l'école a activé le contrôle de capacité
 * (Crèche > Paramètres). Sans effet sur les classes de maternelle et de primaire.
 */
class GroupHasRoom implements ValidationRule
{
    /** @param  int|null  $exceptStudentId  Enfant déjà dans le groupe (modification de sa fiche). */
    public function __construct(private ?int $exceptStudentId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $class = SchoolClass::with('school')->find($value);

        if ($class && $class->isFullFor($this->exceptStudentId)) {
            $fail("Le groupe « {$class->name} » est complet ({$class->capacity} enfants).");
        }
    }
}
