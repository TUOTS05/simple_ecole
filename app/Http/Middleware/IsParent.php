<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Réserve une route aux parents.
 */
class IsParent
{
    /**
     * Refuse l'accès (403) à tout autre rôle.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! auth()->user()->isParent()) {
            abort(403, 'Accès réservé aux parents');
        }

        return $next($request);
    }
}
