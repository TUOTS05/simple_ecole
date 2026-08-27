<?php

use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckTenant;
use App\Http\Middleware\EnsureSchoolIsActive;
use App\Http\Middleware\EnsureTeacherRole;
use App\Http\Middleware\IsParent;
use App\Http\Middleware\PreventDemoWrites;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
            'role' => CheckRole::class,
            'tenant' => CheckTenant::class,
            'teacher' => EnsureTeacherRole::class,
            'parent' => IsParent::class,
            'school.active' => EnsureSchoolIsActive::class,
        ]);

        // Empêche le compte de démo partagé d'écrire en base (sauf déconnexion)
        $middleware->web(append: [
            PreventDemoWrites::class,
            CheckMaintenanceMode::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // notifications:late-payments (SMS) est déjà planifié dans routes/console.php —
        // ne pas le redéclarer ici, sinon les parents reçoivent le rappel deux fois.

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
