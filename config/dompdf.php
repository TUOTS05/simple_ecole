<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paramètres
    |--------------------------------------------------------------------------
    |
    | Options passées à dompdf pour générer les PDF.
    |
    */
    'show_warnings' => false,   // Lever une exception sur les avertissements de dompdf

    'public_path' => null,  // Dossier public à utiliser à la place de celui par défaut

    /*
     * La police DejaVu Sans n'a pas les glyphes des entités converties : désactiver pour afficher € et £.
     */
    'convert_entities' => true,

    'options' => [
        /**
         * Dossier où dompdf stocke les polices et leurs métriques.
         * Il doit exister et être accessible en écriture par le serveur web.
         * Les polices utilisées doivent être incorporées au PDF : cela alourdit le fichier
         * (sauf si le sous-ensemble de polices est activé) et suppose d'en avoir le droit.
         * Toute police demandée dans le HTML est remplacée par la plus proche disponible.
         * Les 14 polices standard du PDF (Courier, Helvetica, Times, Symbol, ZapfDingbats)
         * sont toujours disponibles.
         */
        'font_dir' => storage_path('fonts'), // advised by dompdf (https://github.com/dompdf/dompdf/pull/782)

        /**
         * Dossier du cache des métriques de polices. Il peut être le même que font_dir
         * et doit être accessible en écriture par le serveur web.
         */
        'font_cache' => storage_path('fonts'),

        /**
         * Dossier temporaire, accessible en écriture par le serveur web. Il sert
         * notamment à télécharger les images distantes.
         */
        'temp_dir' => sys_get_temp_dir(),

        /**
         * IMPORTANT : « chroot » de dompdf. Il ne peut lire que les fichiers situés dans
         * ce dossier (chemin absolu) et ses sous-dossiers, ce qui l'empêche d'accéder aux
         * fichiers système. Ne jamais le définir à « / », sous peine d'exposer tout le
         * serveur. Ne concerne que les fichiers locaux, pas les URL distantes.
         */
        'chroot' => realpath(base_path()),

        /**
         * Protocoles et enveloppes PHP autorisés dans les URI, avec les règles qui
         * décident si une ressource peut être chargée.
         */
        'allowed_protocols' => [
            'data://' => ['rules' => []],
            'file://' => ['rules' => []],
            'http://' => ['rules' => []],
            'https://' => ['rules' => []],
        ],

        /**
         * Validation du chemin des fichiers produits (journaux, fichiers temporaires).
         */
        'artifactPathValidation' => null,

        /**
         * Fichier où dompdf écrit son journal (aucun par défaut).
         */
        'log_output_file' => null,

        /**
         * N'incorpore au PDF que les caractères réellement utilisés, pour alléger le fichier.
         */
        'enable_font_subsetting' => false,

        /**
         * Moteur de rendu : « PDFLib », « CPDF » (fourni avec dompdf), « GD » (images,
         * expérimental, sans multipage) ou « auto », qui utilise PDFLib s'il est
         * installé et CPDF sinon. CPDF suffit pour l'usage de l'application.
         */
        'pdf_backend' => 'CPDF',

        /**
         * Type de média CSS utilisé pour le rendu (screen, print…). Même si le PDF est
         * destiné à l'impression, on peut vouloir le rendu « écran » de la page.
         */
        'default_media_type' => 'screen',

        /**
         * Format de papier par défaut : « a4 » (« letter » en Amérique du Nord).
         */
        'default_paper_size' => 'a4',

        /**
         * Orientation par défaut : « portrait » ou « landscape » (paysage).
         */
        'default_paper_orientation' => 'portrait',

        /**
         * Police utilisée quand aucune police adaptée n'est trouvée. Elle doit exister
         * dans le dossier des polices.
         */
        'default_font' => 'serif',

        /**
         * Résolution par défaut des images et des polices, en points par pouce.
         * Elle sert à convertir les tailles en pixels du HTML en points PDF
         * (1 pt = 1/72 de pouce), pour approcher le rendu d'un navigateur (96 DPI).
         * Une image dont la largeur est fixée en CSS garde sa propre résolution ;
         * celle des images d'arrière-plan dépend uniquement de ce réglage.
         */
        'dpi' => 96,

        /**
         * Exécute le PHP contenu dans les balises <script type="text/php">.
         * IMPORTANT : dangereux avec des documents non fiables, ce code s'exécutant
         * avec les droits de dompdf. Laisser à false.
         */
        'enable_php' => false,

        /**
         * Insère dans le PDF le JavaScript des balises <script type="text/javascript">.
         * Il s'agit de JavaScript exécuté par le lecteur PDF, pas par dompdf.
         */
        'enable_javascript' => true,

        /**
         * Autorise dompdf à télécharger images et feuilles de style sur d'autres sites.
         * Risque de sécurité, surtout combiné à enable_php ou à du HTML venant de
         * l'extérieur : à ne modifier qu'en connaissance de cause.
         */
        'enable_remote' => true,

        /**
         * Hôtes distants autorisés quand enable_remote est actif (un nom d'hôte par
         * valeur). Sans effet si enable_remote est désactivé. Null : tout hôte autorisé.
         */
        'allowed_remote_hosts' => null,

        /**
         * Coefficient appliqué à la hauteur des polices, pour se rapprocher de
         * l'interligne des navigateurs.
         */
        'font_height_ratio' => 1.1,

        /**
         * Utilise l'analyseur HTML5.
         */
        'enable_html5_parser' => true,
    ],

];
