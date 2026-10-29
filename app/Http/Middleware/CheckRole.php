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
        if (! $request->user()) {
            return redirect()->route('login');
        }

        // 2. Personnel à fonction configurable : l'accès ne dépend pas de la liste de rôles de la
        // route mais des modules accordés à sa fonction (refus par défaut, voir User::staffCanAccessRoute).
        if ($request->user()->isStaff()) {
            if (! $request->user()->staffCanAccessRoute($request->route()?->getName())) {
                abort(403, 'Accès non autorisé. Votre fonction ne donne pas accès à cette page.');
            }

            return $next($request);
        }

        // 3. Vérifier si le rôle de l'utilisateur est dans la liste autorisée (insensible à la casse,
        // comme User::isParent(), pour éviter qu'un rôle saisi avec une casse différente en base
        // échoue silencieusement ici alors qu'il passerait les autres vérifications de rôle).
        $userRole = strtolower(trim($request->user()->role ?? ''));
        $allowedRoles = array_map(fn ($role) => strtolower(trim($role)), $roles);

        if (! in_array($userRole, $allowedRoles, true)) {
            abort(403, 'Accès non autorisé. Vous n\'avez pas les permissions requises.');
        }

        // 4. Passer la requête au prochain middleware/controller
        return $next($request);
    }
}
