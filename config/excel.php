<?php

use Maatwebsite\Excel\DefaultValueBinder;
use Maatwebsite\Excel\Excel;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

return [
    'exports' => [

        /*
        |--------------------------------------------------------------------------
        | Taille des lots
        |--------------------------------------------------------------------------
        |
        | Avec FromQuery, la requête est découpée automatiquement en lots ; nombre
        | de lignes par lot.
        |
        */
        'chunk_size' => 1000,

        /*
        |--------------------------------------------------------------------------
        | Précalcul des formules à l'export
        |--------------------------------------------------------------------------
        */
        'pre_calculate_formulas' => false,

        /*
        |--------------------------------------------------------------------------
        | Comparaison stricte des valeurs nulles
        |--------------------------------------------------------------------------
        |
        | Si elle est activée, les cellules vides ('') sont ajoutées à la feuille.
        |
        */
        'strict_null_comparison' => false,

        /*
        |--------------------------------------------------------------------------
        | Paramètres CSV
        |--------------------------------------------------------------------------
        |
        | Délimiteur, encadrement des valeurs, fin de ligne, etc. pour les exports
        | CSV.
        |
        */
        'csv' => [
            'delimiter' => ',',
            'enclosure' => '"',
            'line_ending' => PHP_EOL,
            'use_bom' => false,
            'include_separator_line' => false,
            'excel_compatibility' => false,
            'output_encoding' => '',
            'test_auto_detect' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | Propriétés du classeur
        |--------------------------------------------------------------------------
        |
        | Titre, auteur, sujet… appliqués par défaut.
        |
        */
        'properties' => [
            'creator' => '',
            'lastModifiedBy' => '',
            'title' => '',
            'description' => '',
            'subject' => '',
            'keywords' => '',
            'category' => '',
            'manager' => '',
            'company' => '',
        ],
    ],

    'imports' => [

        /*
        |--------------------------------------------------------------------------
        | Lecture seule
        |--------------------------------------------------------------------------
        |
        | À l'import, seules les données sont lues et les styles ignorés. Passer
        | read_only à false pour exploiter aussi les styles.
        |
        */
        'read_only' => true,

        /*
        |--------------------------------------------------------------------------
        | Lignes vides
        |--------------------------------------------------------------------------
        |
        | À l'import, les lignes ne contenant que des valeurs nulles ou vides sont
        | conservées par défaut. Passer ignore_empty à true pour les ignorer.
        |
        */
        'ignore_empty' => false,

        /*
        |--------------------------------------------------------------------------
        | Format de la ligne d'en-tête
        |--------------------------------------------------------------------------
        |
        | Transformation appliquée aux titres de colonnes. Options : none, slug,
        | custom.
        |
        */
        'heading_row' => [
            'formatter' => 'slug',
        ],

        /*
        |--------------------------------------------------------------------------
        | Paramètres CSV
        |--------------------------------------------------------------------------
        |
        | Délimiteur, encadrement des valeurs, fin de ligne, etc. pour les imports
        | CSV.
        |
        */
        'csv' => [
            'delimiter' => null,
            'enclosure' => '"',
            'escape_character' => '\\',
            'contiguous' => false,
            'input_encoding' => Csv::GUESS_ENCODING,
        ],

        /*
        |--------------------------------------------------------------------------
        | Propriétés du classeur
        |--------------------------------------------------------------------------
        |
        | Titre, auteur, sujet… appliqués par défaut.
        |
        */
        'properties' => [
            'creator' => '',
            'lastModifiedBy' => '',
            'title' => '',
            'description' => '',
            'subject' => '',
            'keywords' => '',
            'category' => '',
            'manager' => '',
            'company' => '',
        ],

        /*
       |--------------------------------------------------------------------------
       | Intergiciels de cellule
       |--------------------------------------------------------------------------
       |
       | Traitements exécutés à la lecture de la valeur d'une cellule.
       |
       */
        'cells' => [
            'middleware' => [
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Détection par extension
    |--------------------------------------------------------------------------
    |
    | Type de lecteur ou d'écrivain utilisé quand le format doit être deviné
    | d'après la seule extension du fichier.
    |
    */
    'extension_detector' => [
        'xlsx' => Excel::XLSX,
        'xlsm' => Excel::XLSX,
        'xltx' => Excel::XLSX,
        'xltm' => Excel::XLSX,
        'xls' => Excel::XLS,
        'xlt' => Excel::XLS,
        'ods' => Excel::ODS,
        'ots' => Excel::ODS,
        'slk' => Excel::SLK,
        'xml' => Excel::XML,
        'gnumeric' => Excel::GNUMERIC,
        'htm' => Excel::HTML,
        'html' => Excel::HTML,
        'csv' => Excel::CSV,
        'tsv' => Excel::TSV,

        /*
        |--------------------------------------------------------------------------
        | Moteur PDF
        |--------------------------------------------------------------------------
        |
        | Moteur utilisé par défaut pour les exports PDF. Options : Excel::MPDF,
        | Excel::TCPDF, Excel::DOMPDF.
        |
        */
        'pdf' => Excel::DOMPDF,
    ],

    /*
    |--------------------------------------------------------------------------
    | Liaison des valeurs
    |--------------------------------------------------------------------------
    |
    | Règle qui décide comment une valeur est formatée quand elle est écrite
    | dans une cellule. On peut fournir sa propre classe, par exemple
    | Maatwebsite\Excel\DefaultValueBinder,
    | PhpOffice\PhpSpreadsheet\Cell\StringValueBinder ou
    | PhpOffice\PhpSpreadsheet\Cell\AdvancedValueBinder.
    |
    */
    'value_binder' => [
        'default' => DefaultValueBinder::class,
    ],

    'cache' => [
        /*
        |--------------------------------------------------------------------------
        | Cache des cellules
        |--------------------------------------------------------------------------
        |
        | PhpSpreadsheet garde par défaut toutes les cellules en mémoire, ce qui
        | peut saturer la mémoire sur de gros fichiers. Le pilote « illuminate »
        | stocke chaque valeur dans le cache (plus lent) ; le pilote « batch » ne
        | l'y écrit qu'une fois la limite mémoire atteinte. Pilotes : memory,
        | illuminate, batch.
        |
        */
        'driver' => 'memory',

        /*
        |--------------------------------------------------------------------------
        | Cache par lots
        |--------------------------------------------------------------------------
        |
        | Avec le pilote « batch », les cellules ne sont écrites dans le cache
        | qu'une fois cette limite mémoire atteinte.
        |
        */
        'batch' => [
            'memory_limit' => 60000,
        ],

        /*
        |--------------------------------------------------------------------------
        | Cache Laravel
        |--------------------------------------------------------------------------
        |
        | Avec le pilote « illuminate », le stockage de cache par défaut est utilisé
        | ; on peut en désigner un autre, défini dans config/cache.php. Laisser «
        | null » pour le stockage par défaut.
        |
        */
        'illuminate' => [
            'store' => null,
        ],

        /*
        |--------------------------------------------------------------------------
        | Durée de vie du cache
        |--------------------------------------------------------------------------
        |
        | Durée de conservation des éléments mis en cache : null pour une durée
        | illimitée, sinon un nombre de secondes, un \DateInterval ou une fonction.
        |
        */
        'default_ttl' => 10800,
    ],

    /*
    |--------------------------------------------------------------------------
    | Transactions
    |--------------------------------------------------------------------------
    |
    | Chaque import est exécuté dans une transaction, annulée en cas d'échec
    | pour pouvoir relancer l'import proprement. Mettre null pour désactiver.
    | Gestionnaires : null, db.
    |
    */
    'transactions' => [
        'handler' => 'db',
        'db' => [
            'connection' => null,
        ],
    ],

    'temporary_files' => [

        /*
        |--------------------------------------------------------------------------
        | Dossier temporaire local
        |--------------------------------------------------------------------------
        |
        | Les exports et imports passent par un fichier temporaire avant d'être
        | enregistrés, lus ou téléchargés. Emplacement de ce fichier.
        |
        */
        'local_path' => storage_path('framework/cache/laravel-excel'),

        /*
        |--------------------------------------------------------------------------
        | Droits du dossier temporaire
        |--------------------------------------------------------------------------
        |
        | Droits du dossier (dir) et des fichiers créés (file). Sans valeur, les
        | droits par défaut du système de fichiers s'appliquent.
        |
        */
        'local_permissions' => [
        ],

        /*
        |--------------------------------------------------------------------------
        | Disque temporaire distant
        |--------------------------------------------------------------------------
        |
        | Avec plusieurs serveurs et des files d'attente, le fichier temporaire peut
        | être placé sur un disque partagé. Laisser null pour toujours utiliser le
        | dossier local. Ne concerne que les imports en file d'attente.
        |
        */
        'remote_disk' => null,
        'remote_prefix' => null,

        /*
        |--------------------------------------------------------------------------
        | Resynchronisation forcée
        |--------------------------------------------------------------------------
        |
        | Avec plusieurs serveurs, le nettoyage final peut ne supprimer le fichier
        | temporaire que sur le dernier serveur, et saturer le stockage des autres.
        | Activer cette option force chaque serveur à reprendre le fichier depuis le
        | disque distant puis à le nettoyer.
        |
        */
        'force_resync_remote' => null,
    ],
];
