<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autorise l'accès si l'utilisateur possède AU MOINS UN des droits listés
 * (ex. permission:enrollments.manage,payments.manage). Voir App\Support\Permissions.
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        foreach ($permissions as $permission) {
            if ($user->hasPermission(trim($permission))) {
                return $next($request);
            }
        }

        abort(403, "Accès non autorisé. Demandez à votre directeur d'activer ce droit sur votre compte.");
    }
}
