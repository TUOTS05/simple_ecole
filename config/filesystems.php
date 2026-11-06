<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Disque par défaut
    |--------------------------------------------------------------------------
    |
    | Disque utilisé par défaut par le framework. Les disques local et cloud
    | sont disponibles.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Disques
    |--------------------------------------------------------------------------
    |
    | Disques de stockage de l'application. Plusieurs disques peuvent utiliser
    | le même pilote. Pilotes : « local », « ftp », « sftp », « s3 ».
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            // 'serve' était à true sans 'url' dédié : Laravel enregistrait alors une route
            // /storage/{path} pour ce disque *privé*, sur la même URL que le disque "public"
            // (qui, lui, est exposé via le lien symbolique storage:link). Cette route entrait
            // en conflit avec les liens vers les cartes/reçus/photos déjà générés, et les
            // servait avec ce disque privé (visibility non publique) → 403 même sur un fichier
            // existant. Ce disque n'est utilisé par aucune fonctionnalité de l'appli qui aurait
            // besoin d'être servie par URL, d'où sa désactivation ici.
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Liens symboliques
    |--------------------------------------------------------------------------
    |
    | Liens créés par la commande Artisan storage:link. Chaque clé est
    | l'emplacement du lien, chaque valeur sa cible.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
