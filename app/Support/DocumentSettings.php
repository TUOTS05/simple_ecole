<?php

namespace App\Support;

use App\Models\School;

/**
 * Personnalisation des documents PDF (bulletin, carte scolaire, reçu) propre à chaque école.
 *
 * Stockée dans schools.settings['documents'] et fusionnée avec DEFAULTS : une école qui n'a
 * jamais rien configuré obtient exactement le rendu historique, et une clé ajoutée plus tard
 * dans DEFAULTS s'applique d'office aux réglages déjà enregistrés.
 */
class DocumentSettings
{
    public const DEFAULTS = [
        'header' => [
            // Lignes officielles au-dessus du nom de l'école (pays, devise, ministère…), une par ligne
            'lines' => '',
            'motto' => '',
            'city' => '',
        ],
        'branding' => [
            'primary_color' => '#123b76',
            'accent_color' => '#e8a317',
            'stamp' => null,
            'signature' => null,
            'signatory_title' => 'La Direction',
            'signatory_name' => '',
        ],
        'report_card' => [
            'title_monthly' => 'Bulletin mensuel',
            'title_quarterly' => 'Bulletin trimestriel',
            'show_rank' => true,
            'show_class_size' => true,
            'show_council_mention' => true,
            'show_stamp' => true,
            'teacher_box_title' => 'Appréciation générale & observations',
            'footer_note' => '',
            // Barème des appréciations, note minimale sur 20 => libellé, du plus haut au plus bas
            'scale' => [
                ['min' => 18, 'label' => 'Excellent'],
                ['min' => 16, 'label' => 'Très Bien'],
                ['min' => 14, 'label' => 'Bien'],
                ['min' => 12, 'label' => 'Assez Bien'],
                ['min' => 10, 'label' => 'Passable'],
                ['min' => 0, 'label' => 'Insuffisant'],
            ],
        ],
        'student_card' => [
            'title' => "Carte d'identité scolaire",
            'show_birth_date' => true,
            'show_gender' => true,
            'footer_note' => '',
        ],
        'receipt' => [
            'title' => 'Reçu de paiement',
            'currency' => 'FCFA',
            'show_pending_installments' => true,
            'show_stamp' => true,
            'signature_label' => 'Signature / Cachet',
            'footer_text' => 'Merci pour votre confiance !',
        ],
    ];

    /**
     * Réglages des documents d'une école, complétés par les valeurs par défaut.
     */
    public static function for(?School $school): array
    {
        $saved = $school?->settings['documents'] ?? [];
        $settings = [];

        foreach (self::DEFAULTS as $section => $defaults) {
            $settings[$section] = array_merge($defaults, $saved[$section] ?? []);
        }

        return $settings;
    }

    /**
     * Libellé du barème correspondant à une note ramenée sur 20.
     */
    public static function appreciation(array $scale, float $scoreOutOf20): string
    {
        usort($scale, fn ($a, $b) => $b['min'] <=> $a['min']);

        foreach ($scale as $step) {
            if ($scoreOutOf20 >= $step['min']) {
                return $step['label'];
            }
        }

        return end($scale)['label'] ?? '--';
    }

    /**
     * Chemin absolu d'une image (logo, cachet, signature) stockée sur le disque public, ou null
     * si elle est absente — dompdf a besoin d'un chemin fichier, pas d'une URL.
     */
    public static function imagePath(?string $relativePath): ?string
    {
        if (! $relativePath) {
            return null;
        }

        $path = public_path('storage/'.$relativePath);

        return file_exists($path) ? $path : null;
    }
}
