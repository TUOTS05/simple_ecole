<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Le Super Admin a toujours accès (il doit pouvoir gérer les écoles suspendues)
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        // 2. Récupérer l'ID de l'école associée à l'utilisateur ou en session
        $schoolId = session('current_school_id') ?? $user->school_id ?? null;

        if ($schoolId) {
            $school = School::find($schoolId);

            if ($school) {
                // 3. Vérifier si l'école est manuellement suspendue
                if ($school->status === 'suspended') {
                    auth()->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')->withErrors([
                        'email' => '🚫 Votre compte a été suspendu par l\'administrateur. Veuillez nous contacter pour régulariser la situation.',
                    ]);
                }

                // 4. Vérifier si l'abonnement est expiré
                if ($school->isExpired()) {
                    auth()->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    $expiryDate = $school->subscription_end_date ?? $school->trial_ends_at;

                    return redirect()->route('login')->withErrors([
                        'email' => '⏳ L\'abonnement de votre école a expiré'.($expiryDate ? ' le '.$expiryDate->format('d/m/Y') : '').'. Veuillez contacter le support pour le renouveler.',
                    ]);
                }
            }
        }

        // 5. Tout est bon, on laisse passer la requête
        return $next($request);
    }
}
