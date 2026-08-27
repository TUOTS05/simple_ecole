<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventDemoWrites
{
    protected const DEMO_EMAIL = 'demo@schoolmanager.com';

    /**
     * Bloque les actions d'écriture du compte de démo partagé, pour que
     * les visiteurs ne puissent pas casser les données de démo des uns
     * pour les autres. La déconnexion reste toujours autorisée.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && $user->email === self::DEMO_EMAIL
            && ! $request->isMethodCacheable()
            && $request->route()?->getName() !== 'logout'
        ) {
            return back()->with('error', '🔒 Action désactivée en mode démonstration : les données ne peuvent pas être modifiées.');
        }

        return $next($request);
    }
}
