<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  ...$roles  Les rôles autorisés (ex: 'super_admin', 'school_admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // 1. Vérifier si l'utilisateur est connecté
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // 2. Vérifier si le rôle de l'utilisateur est dans la liste autorisée
        if (!in_array($request->user()->role, $roles)) {
            abort(403, 'Accès non autorisé. Vous n\'avez pas les permissions requises.');
        }

        // 3. Passer la requête au prochain middleware/controller
        return $next($request);
    }
}