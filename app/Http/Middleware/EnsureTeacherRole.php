<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Réserve une route aux enseignants.
 */
class EnsureTeacherRole
{
    /**
     * Refuse l'accès (403) à tout autre rôle.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si l'utilisateur est connecté et a le rôle 'teacher' (insensible à la casse,
        // cohérent avec CheckRole et User::isParent()).
        if (! auth()->check() || strtolower(trim(auth()->user()->role ?? '')) !== 'teacher') {
            abort(403, 'Accès réservé aux enseignants.');
        }

        return $next($request);
    }
}
