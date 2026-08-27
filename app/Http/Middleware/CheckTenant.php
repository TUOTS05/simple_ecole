<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Vérifier si l'utilisateur est connecté
        if (! $request->user()) {
            return redirect()->route('login');
        }

        $user = $request->user();

        // 2. Vérifier si l'utilisateur a un school_id (sinon c'est un super_admin)
        if (! $user->school_id) {
            // Super Admin essaie d'accéder à une route école
            return redirect()->route('superadmin.dashboard');
        }

        // 3. Récupérer l'école de l'utilisateur
        $school = School::find($user->school_id);

        // 4. Vérifier que l'école existe et est active
        if (! $school) {
            abort(403, 'Votre école n\'existe pas.');
        }

        if ($school->status !== 'active') {
            abort(403, 'Votre école n\'est pas active. Statut actuel : '.$school->status);
        }

        // 5. Stocker le school_id dans la session pour accès global
        session(['current_school_id' => $user->school_id]);
        session(['current_school' => $school]);

        // 6. Rendre le school_id disponible via un singleton
        app()->instance('current_school_id', $user->school_id);
        app()->instance('current_school', $school);

        // 7. Passer la requête au prochain middleware/controller
        return $next($request);
    }
}
