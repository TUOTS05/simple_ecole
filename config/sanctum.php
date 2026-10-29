<?php

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Laravel\Sanctum\Http\Middleware\AuthenticateSession;
use Laravel\Sanctum\Sanctum;

return [

    /*
    |--------------------------------------------------------------------------
    | Domaines avec session
    |--------------------------------------------------------------------------
    |
    | Les requêtes venant de ces domaines reçoivent des cookies
    | d'authentification avec état. En général, les domaines locaux et de
    | production qui consomment l'API depuis un SPA.
    |
    */

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort(),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Gardes Sanctum
    |--------------------------------------------------------------------------
    |
    | Gardes essayées, dans l'ordre, pour authentifier une requête. Si aucune ne
    | réussit, Sanctum utilise le jeton Bearer de la requête.
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration des jetons
    |--------------------------------------------------------------------------
    |
    | Durée de validité, en minutes, d'un jeton émis. Ne s'applique pas aux
    | sessions d'une application monopage. Null : pas d'expiration.
    |
    */

    'expiration' => null,

    /*
    |--------------------------------------------------------------------------
    | Préfixe des jetons
    |--------------------------------------------------------------------------
    |
    | Préfixe ajouté aux nouveaux jetons, pour permettre aux outils d'analyse de
    | secrets de les détecter s'ils fuitent dans un dépôt.
    |
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Intergiciels Sanctum
    |--------------------------------------------------------------------------
    |
    | Intergiciels exécutés pour authentifier les requêtes d'une application
    | monopage. Ils peuvent être remplacés par des versions personnalisées.
    |
    */

    'middleware' => [
        'authenticate_session' => AuthenticateSession::class,
        'encrypt_cookies' => EncryptCookies::class,
        'validate_csrf_token' => ValidateCsrfToken::class,
    ],

];
