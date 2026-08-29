<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Evite l'erreur MySQL "Specified key was too long" sur les index
        // composites (utf8mb4 = 4 octets/caractère, max 3072 octets par clé).
        Schema::defaultStringLength(191);

        // Configuration du rate limiting pour l'API
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Ping de position GPS des véhicules : route publique (aucune session), donc
        // à protéger explicitement. Indexé sur le jeton et non sur l'IP, car plusieurs
        // chauffeurs peuvent sortir derrière la même IP d'opérateur mobile (NAT).
        // La page chauffeur envoie au plus 4 pings/minute : 20 laisse de la marge.
        RateLimiter::for('vehicle-ping', function (Request $request) {
            return Limit::perMinute(20)->by((string) $request->route('token'));
        });

        // Personnalisation de la redirection après login
        $this->configureRedirects();
    }

    /**
     * Configurer les redirections après authentification selon le rôle
     */
    private function configureRedirects(): void
    {
        // Redirection après login selon le rôle
        Route::middleware('web')->group(function () {
            // Intercepter la route /dashboard par défaut de Breeze
            Route::get('/dashboard', function () {
                $user = auth()->user();

                return redirect()->route($user->dashboardRouteName());
            })->middleware('auth')->name('dashboard');
        });
    }
}
