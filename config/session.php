<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Stockage des sessions
    |--------------------------------------------------------------------------
    |
    | Pilote utilisé pour stocker les sessions. « database » convient bien par
    | défaut. Pilotes : « file », « cookie », « database », « memcached », «
    | redis », « dynamodb », « array ».
    |
    */

    'driver' => env('SESSION_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Durée de vie de la session
    |--------------------------------------------------------------------------
    |
    | Nombre de minutes d'inactivité avant l'expiration de la session. Pour
    | qu'elle expire à la fermeture du navigateur, activer « expire_on_close ».
    |
    */

    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    /*
    |--------------------------------------------------------------------------
    | Chiffrement de la session
    |--------------------------------------------------------------------------
    |
    | Si actif, toutes les données de session sont chiffrées avant d'être
    | stockées, automatiquement.
    |
    */

    'encrypt' => env('SESSION_ENCRYPT', false),

    /*
    |--------------------------------------------------------------------------
    | Emplacement des fichiers de session
    |--------------------------------------------------------------------------
    |
    | Avec le pilote « file », dossier où sont stockés les fichiers de session.
    |
    */

    'files' => storage_path('framework/sessions'),

    /*
    |--------------------------------------------------------------------------
    | Connexion de la base des sessions
    |--------------------------------------------------------------------------
    |
    | Avec les pilotes « database » ou « redis », connexion utilisée pour gérer
    | les sessions. Elle doit correspondre à une connexion de
    | config/database.php.
    |
    */

    'connection' => env('SESSION_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Table des sessions
    |--------------------------------------------------------------------------
    |
    | Avec le pilote « database », table où sont stockées les sessions.
    |
    */

    'table' => env('SESSION_TABLE', 'sessions'),

    /*
    |--------------------------------------------------------------------------
    | Stockage de cache des sessions
    |--------------------------------------------------------------------------
    |
    | Avec un pilote basé sur le cache, stockage utilisé pour les sessions. Il
    | doit correspondre à un stockage défini dans config/cache.php.
    |
    */

    'store' => env('SESSION_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Nettoyage des sessions expirées
    |--------------------------------------------------------------------------
    |
    | Certains pilotes doivent purger les anciennes sessions eux-mêmes. Chances
    | qu'une requête déclenche ce nettoyage (2 sur 100 par défaut).
    |
    */

    'lottery' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Nom du cookie de session
    |--------------------------------------------------------------------------
    |
    | Nom du cookie qui identifie la session. Il est rarement utile de le
    | changer.
    |
    */

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug((string) env('APP_NAME', 'laravel')).'-session'
    ),

    /*
    |--------------------------------------------------------------------------
    | Chemin du cookie de session
    |--------------------------------------------------------------------------
    |
    | Chemin pour lequel le cookie est valable. En général la racine de
    | l'application.
    |
    */

    'path' => env('SESSION_PATH', '/'),

    /*
    |--------------------------------------------------------------------------
    | Domaine du cookie de session
    |--------------------------------------------------------------------------
    |
    | Domaine et sous-domaines pour lesquels le cookie est valable. Par défaut,
    | le domaine courant et ses sous-domaines.
    |
    */

    'domain' => env('SESSION_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Cookies HTTPS uniquement
    |--------------------------------------------------------------------------
    |
    | Si actif, le cookie de session n'est renvoyé que sur une connexion HTTPS.
    |
    */

    'secure' => env('SESSION_SECURE_COOKIE'),

    /*
    |--------------------------------------------------------------------------
    | Accès HTTP uniquement
    |--------------------------------------------------------------------------
    |
    | Empêche JavaScript de lire le cookie de session : il ne transite que par
    | le protocole HTTP.
    |
    */

    'http_only' => env('SESSION_HTTP_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Cookies SameSite
    |--------------------------------------------------------------------------
    |
    | Comportement du cookie lors des requêtes venant d'autres sites, pour se
    | protéger des attaques CSRF. Valeur par défaut : « lax ». Valeurs : « lax
    | », « strict », « none », null.
    |
    */

    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    /*
    |--------------------------------------------------------------------------
    | Cookies partitionnés
    |--------------------------------------------------------------------------
    |
    | Lie le cookie au site de premier niveau dans un contexte intersite. Exige
    | les options « secure » et « same_site » à « none ».
    |
    */

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

    /*
    |--------------------------------------------------------------------------
    | Sérialisation de la session
    |--------------------------------------------------------------------------
    |
    | Format de sérialisation des données de session. « json » est recommandé ;
    | « php » ne doit être utilisé que si la session doit contenir des objets
    | PHP. Valeurs : « json », « php ».
    |
    */

    'serialization' => 'json',

];
