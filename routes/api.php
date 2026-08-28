<?php

use App\Http\Controllers\CinetPayWebhookController;
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
