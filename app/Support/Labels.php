<?php

namespace App\Support;

/**
 * Libellés français des valeurs techniques enregistrées en anglais (statuts, types, modes de paiement).
 */
class Labels
{
    /**
     * Libellé de la valeur, ou la valeur elle-même (première lettre en majuscule) si elle n'est pas traduite.
     */
    public static function of(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $key = "statuts.{$value}";
        $label = __($key);

        return $label === $key ? ucfirst($value) : $label;
    }
}
