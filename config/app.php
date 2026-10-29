<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nom de l'application
    |--------------------------------------------------------------------------
    |
    | Nom affiché par le framework dans les notifications et les autres éléments
    | d'interface.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Environnement
    |--------------------------------------------------------------------------
    |
    | Environnement d'exécution (local, production…). Il peut changer la
    | configuration de certains services. À définir dans le fichier « .env ».
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Mode débogage
    |--------------------------------------------------------------------------
    |
    | Quand il est actif, les erreurs s'affichent en détail avec la pile
    | d'appels. Sinon, une page d'erreur générique est affichée. À désactiver
    | absolument en production.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | URL de l'application
    |--------------------------------------------------------------------------
    |
    | Adresse utilisée par les commandes Artisan pour générer des liens. À
    | renseigner avec la racine de l'application.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Fuseau horaire
    |--------------------------------------------------------------------------
    |
    | Fuseau horaire par défaut, utilisé par les fonctions de date de PHP. Par
    | défaut UTC, qui convient à la plupart des usages.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Langue de l'application
    |--------------------------------------------------------------------------
    |
    | Langue par défaut des traductions et de la localisation, langue de repli
    | quand une traduction manque, et langue utilisée par Faker pour générer de
    | fausses données.
    |
    */

    'locale' => env('APP_LOCALE', 'fr'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'fr'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'fr_FR'),

    /*
    |--------------------------------------------------------------------------
    | Clé de chiffrement
    |--------------------------------------------------------------------------
    |
    | Clé utilisée par les services de chiffrement de Laravel. Elle doit être
    | une chaîne aléatoire de 32 caractères, définie avant le déploiement.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mode maintenance
    |--------------------------------------------------------------------------
    |
    | Mécanisme qui mémorise l'état « en maintenance ». Le pilote « cache »
    | permet de le piloter sur plusieurs serveurs à la fois. Pilotes possibles :
    | « file », « cache ».
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
