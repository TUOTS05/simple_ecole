<?php

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
