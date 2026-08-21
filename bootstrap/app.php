<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Console\Scheduling\Schedule;


return Application::configure(basePath: dirname(__DIR__))
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php', // ← AJOUTÉ : Routes API pour mobile
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Configuration Sanctum pour l'API
        $middleware->statefulApi();
        
        // Alias de middleware pour les rôles
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'tenant' => \App\Http\Middleware\CheckTenant::class,
            'teacher' => \App\Http\Middleware\EnsureTeacherRole::class,
            'parent' => \App\Http\Middleware\IsParent::class,
            'school.active' => \App\Http\Middleware\EnsureSchoolIsActive::class,
        ]);

        // Empêche le compte de démo partagé d'écrire en base (sauf déconnexion)
        $middleware->web(append: [
            \App\Http\Middleware\PreventDemoWrites::class,
            \App\Http\Middleware\CheckMaintenanceMode::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Exécuter tous les jours à 9h du matin
        $schedule->command('notifications:late-payments')
                ->dailyAt('09:00')
             ->withoutOverlapping();

        // Le rappel d'expiration d'abonnement/essai gratuit (notify:schools-expiring) est déjà
        // planifié dans routes/console.php — ne pas le redéclarer ici pour éviter un double envoi.

        // Réinitialise l'école de démo chaque nuit pour effacer ce que les visiteurs y ont modifié
        $schedule->command('demo:reset')
                ->dailyAt('04:00')
                ->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Rendre JSON les erreurs pour l'API
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
