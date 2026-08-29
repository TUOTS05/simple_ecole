<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ParentExtraController;
use App\Http\Controllers\CinetPayWebhookController;
use App\Http\Controllers\VehicleTrackingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Routes API pour l'application mobile (parents, enseignants)
|
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------
| API parent (application mobile) — jetons Sanctum
|--------------------------------------------------------------------
|
| Lecture seule : consulter ses enfants, leurs extras, échéances,
| paiements et la position du bus. Souscrire et payer restent sur le
| site web. Voir Api\ParentExtraController.
|
*/

// Limite serrée : c'est la seule route publique qui teste un mot de passe.
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('api.login');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');

    Route::get('/children', [ParentExtraController::class, 'children'])->name('api.children');

    Route::prefix('children/{student}')->group(function () {
        Route::get('/extras', [ParentExtraController::class, 'subscriptions'])->name('api.extras');
        Route::get('/extras/{subscription}/installments', [ParentExtraController::class, 'installments'])->name('api.extras.installments');
        Route::get('/extras/{subscription}/payments', [ParentExtraController::class, 'payments'])->name('api.extras.payments');
        Route::get('/extras/{subscription}/bus', [ParentExtraController::class, 'busPosition'])->name('api.extras.bus');
    });
});

// Webhook public CinetPay (notify_url) : pas d'auth, appelé directement par
// les serveurs CinetPay. Le groupe "api" n'a pas de vérification CSRF.
Route::post('/webhooks/cinetpay/extras', [CinetPayWebhookController::class, 'notify'])->name('webhooks.cinetpay.extras');

// Ping de position GPS envoyé par la page chauffeur (JS, en boucle) — sécurisé par le
// jeton opaque dans l'URL, pas de session/CSRF (le groupe "api" n'en a pas).
// throttle explicite : depuis Laravel 11 le groupe "api" n'applique plus de limite
// par défaut, et cette route publique écrit une ligne en base à chaque appel.
Route::post('/track/{token}/ping', [VehicleTrackingController::class, 'ping'])
    ->middleware('throttle:vehicle-ping')
    ->name('vehicle-tracking.ping');
