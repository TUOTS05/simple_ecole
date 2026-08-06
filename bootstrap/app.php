<?php

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
            'role' => \App\Http\Middleware\CheckRole::class,
            'tenant' => \App\Http\Middleware\CheckTenant::class,
            'teacher' => \App\Http\Middleware\EnsureTeacherRole::class,
            'mobile' => \App\Http\Middleware\MobileOnly::class,
            'parent' => \App\Http\Middleware\IsParent::class,
            'school.active' => \App\Http\Middleware\EnsureSchoolIsActive::class,
        ]);
        
        // Groupes de middleware
        $middleware->group('admin', [
            'auth',
            'role:super_admin,school_admin',
        ]);
        
        $middleware->group('school_admin', [
            'auth',
            'role:school_admin',
            'tenant',
        ]);
       
        
        $middleware->group('teacher', [
            'auth',
            'role:teacher',
            'tenant',
        ]);
        
        $middleware->group('parent', [
            'auth',
            'role:parent',
            'tenant',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Rendre JSON les erreurs pour l'API
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();