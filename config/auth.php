<?php

use App\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentification par défaut
    |--------------------------------------------------------------------------
    |
    | Garde et mécanisme de réinitialisation du mot de passe utilisés par
    | défaut. Modifiable à volonté, mais ces valeurs conviennent à la plupart
    | des applications.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Gardes d'authentification
    |--------------------------------------------------------------------------
    |
    | Chaque garde définit comment les utilisateurs sont authentifiés. La
    | configuration par défaut utilise les sessions et le fournisseur Eloquent.
    | Pilote possible : « session ».
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fournisseurs d'utilisateurs
    |--------------------------------------------------------------------------
    |
    | Indique comment les utilisateurs sont lus depuis la base de données.
    | Chaque garde utilise un fournisseur. Pilotes possibles : « database », «
    | eloquent ».
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Réinitialisation des mots de passe
    |--------------------------------------------------------------------------
    |
    | Table des jetons de réinitialisation et durée de validité d'un jeton (en
    | minutes, 60 par défaut). « throttle » impose un délai en secondes entre
    | deux demandes de jeton, pour limiter les abus.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Délai de confirmation du mot de passe
    |--------------------------------------------------------------------------
    |
    | Nombre de secondes avant que l'utilisateur doive de nouveau confirmer son
    | mot de passe sur un écran sensible (3 heures par défaut).
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
