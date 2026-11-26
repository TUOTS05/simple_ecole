<?php

namespace App\Support;

use App\Models\School;
use App\Models\Student;

/**
 * Génère le matricule et le numéro d'admission d'un élève selon le format choisi par l'école
 * (ex. « {ANNEE}-{CODE}-{NUM} » → 2026-LAP-001).
 *
 * Le numéro suivant est déduit des numéros déjà attribués ayant le même préfixe/suffixe :
 * un format contenant {ANNEE} repart donc à zéro chaque année, sans compteur à stocker.
 */
class SchoolNumbering
{
    /** Colonne de la table students pour chaque type de numéro. */
    private const COLUMNS = [
        'matricule' => 'matricule',
        'admission' => 'admission_number',
    ];

    public static function next(School $school, string $type, ?int $year = null): string
    {
        $column = self::COLUMNS[$type];
        [$prefix, $suffix] = self::parts($school, $type, $year);
        $digits = max(1, min(8, (int) SchoolSettings::get($school, "{$type}.digits")));
        $start = max(1, (int) SchoolSettings::get($school, "{$type}.start"));

        $existing = Student::withTrashed()
            ->where('school_id', $school->id)
            // Pré-filtre large (les jokers SQL éventuels ne font qu'élargir), filtrage exact par regex ensuite.
            ->where($column, 'like', $prefix.'%'.$suffix)
            ->pluck($column);

        $max = 0;
        $pattern = '/^'.preg_quote($prefix, '/').'(\d+)'.preg_quote($suffix, '/').'$/';
        foreach ($existing as $value) {
            if (preg_match($pattern, (string) $value, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        $number = max($start, $max + 1);

        // Le matricule est unique sur toute la plateforme : on saute un numéro déjà pris
        // (par exemple par une autre école ayant le même code).
        do {
            $candidate = $prefix.str_pad((string) $number, $digits, '0', STR_PAD_LEFT).$suffix;
            $taken = $type === 'matricule' && Student::withTrashed()->where('matricule', $candidate)->exists();
            $number++;
        } while ($taken);

        return $candidate;
    }

    /** Aperçu d'un format (page de personnalisation), sans accès aux élèves. */
    public static function preview(string $pattern, string $code, int $digits, int $number = 1, ?int $year = null): string
    {
        $year ??= (int) date('Y');
        $pattern = str_contains($pattern, '{NUM}') ? $pattern : $pattern.'{NUM}';

        return strtr($pattern, [
            '{ANNEE}' => (string) $year,
            '{AA}' => substr((string) $year, -2),
            '{CODE}' => $code,
            '{NUM}' => str_pad((string) $number, max(1, $digits), '0', STR_PAD_LEFT),
        ]);
    }

    /** @return array{0: string, 1: string} préfixe et suffixe autour de {NUM} */
    private static function parts(School $school, string $type, ?int $year): array
    {
        $year ??= (int) date('Y');
        $pattern = (string) SchoolSettings::get($school, "{$type}.pattern");
        $pattern = str_contains($pattern, '{NUM}') ? $pattern : $pattern.'{NUM}';
        $code = strtoupper((string) (SchoolSettings::get($school, 'matricule.code') ?: SchoolSettings::defaultCode($school)));

        $rendered = strtr($pattern, [
            '{ANNEE}' => (string) $year,
            '{AA}' => substr((string) $year, -2),
            '{CODE}' => $code,
        ]);

        $pos = strpos($rendered, '{NUM}');

        return [substr($rendered, 0, $pos), substr($rendered, $pos + 5)];
    }
}
