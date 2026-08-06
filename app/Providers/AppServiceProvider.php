<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

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
        // Configuration du rate limiting pour l'API
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
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
