<?php

namespace App\Support;

use App\Models\School;

/**
 * Personnalisation d'une école, stockée dans schools.settings (JSON).
 *
 * Chaque école ne stocke que ce qu'elle a modifié : les valeurs absentes retombent sur
 * DEFAULTS, qui reproduisent exactement le comportement d'avant la personnalisation.
 */
class SchoolSettings
{
    public const DEFAULT_SUBJECTS = [
        'Lecture', 'Écriture/Copie', 'Orthographe', 'Grammaire/Conjugaison', 'Vocabulaire/EDHC',
        'Exploitation de texte', 'Expression Écrite', 'Éveil du Milieu', 'Sciences', 'Mathématique',
        'Chant/Récitation', 'Dessin', 'EPS', 'Dictée', 'Anglais',
    ];

    public const DEFAULTS = [
        // Espace / identité
        'identity' => [
            'short_name' => null,        // Sigle affiché dans le menu (ex. « GS TTS »)
            'motto' => null,             // Devise (bulletins)
            'city' => null,              // Ville du « Fait à » des documents
            'header_line1' => null,      // Ex. « République de Côte d'Ivoire »
            'header_line2' => null,      // Ex. « Ministère de l'Éducation Nationale »
            'primary_color' => '#87CEEB',
        ],
        // Matricule de l'élève
        'matricule' => [
            'pattern' => '{ANNEE}-{CODE}-{NUM}',
            'code' => null,              // null = 3 premiers caractères du nom court de l'école
            'digits' => 3,
            'start' => 1,
        ],
        // Numéro d'admission
        'admission' => [
            'pattern' => 'ADM-{ANNEE}-{NUM}',
            'digits' => 4,
            'start' => 1,
        ],
        // Bulletins
        'report_card' => [
            'subject_scale' => [
                ['min' => 18, 'label' => 'Excellent'],
                ['min' => 16, 'label' => 'Très Bien'],
                ['min' => 14, 'label' => 'Bien'],
                ['min' => 12, 'label' => 'Assez Bien'],
                ['min' => 10, 'label' => 'Passable'],
                ['min' => 0, 'label' => 'Insuffisant'],
            ],
            'general_scale' => [
                ['min' => 16, 'label' => 'Très Bien'],
                ['min' => 14, 'label' => 'Bien'],
                ['min' => 12, 'label' => 'Assez Bien'],
                ['min' => 10, 'label' => 'Passable'],
                ['min' => 0, 'label' => 'Insuffisant'],
            ],
            'show_rank' => true,
            'show_class_size' => true,
            'show_coefficient' => true,
            'show_mention' => true,
            'group_by_domain' => true,
            'mention_title' => 'MENTION DU CONSEIL',
            'left_box_title' => 'APPRÉCIATION GÉNÉRALE & OBSERVATIONS',
            'right_box_title' => 'LA DIRECTION/ CACHET & SIGNATURE',
            'director_name' => null,
            'footer_note' => null,
        ],
        // Matières : liste proposée par défaut à la configuration d'un niveau
        'subjects' => [
            'maternelle' => self::DEFAULT_SUBJECTS,
            'primaire' => self::DEFAULT_SUBJECTS,
        ],
    ];

    public const TOKENS = [
        '{ANNEE}' => 'Année sur 4 chiffres (2026)',
        '{AA}' => 'Année sur 2 chiffres (26)',
        '{CODE}' => "Code de l'école",
        '{NUM}' => 'Numéro séquentiel',
    ];

    public static function get(?School $school, string $key): mixed
    {
        $default = data_get(self::DEFAULTS, $key);
        $value = data_get($school?->settings ?? [], $key);

        return $value ?? $default;
    }

    /** Fusionne les valeurs saisies dans les réglages existants de l'école. */
    public static function merge(School $school, array $values): array
    {
        return array_replace_recursive($school->settings ?? [], $values);
    }

    /** Libellé correspondant à une note sur 20 selon un barème [['min' => x, 'label' => y], ...]. */
    public static function labelFor(array $scale, float $scoreOutOf20): string
    {
        usort($scale, fn ($a, $b) => $b['min'] <=> $a['min']);

        foreach ($scale as $step) {
            if ($scoreOutOf20 >= (float) $step['min']) {
                return (string) $step['label'];
            }
        }

        return (string) (end($scale)['label'] ?? '');
    }

    /** Assombrit une couleur #RRGGBB (survol des boutons « primary-dark »). */
    public static function darken(string $hex, float $ratio = 0.18): string
    {
        if (! preg_match('/^#([0-9A-Fa-f]{6})$/', $hex, $m)) {
            return '#5FB3D9';
        }

        $channels = array_map(fn ($pair) => (int) round(hexdec($pair) * (1 - $ratio)), str_split($m[1], 2));

        return sprintf('#%02X%02X%02X', ...$channels);
    }

    /** Code école par défaut : 3 premiers caractères alphanumériques du nom court ou du slug. */
    public static function defaultCode(School $school): string
    {
        $source = self::get($school, 'identity.short_name') ?: $school->slug ?: $school->name;
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $source));

        return substr($code, 0, 3) ?: 'ECO';
    }
}
