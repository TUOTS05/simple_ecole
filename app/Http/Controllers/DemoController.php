<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Accès au compte de démonstration depuis la page d'accueil.
 */
class DemoController extends Controller
{
    /**
     * Connecte directement le visiteur au compte de démonstration, s'il existe.
     */
    public function login()
    {
        // Trouver l'utilisateur de démo
        $demoUser = User::where('email', 'demo@schoolmanager.com')->first();

        if (! $demoUser) {
            return redirect('/')->with('error', 'Le compte de démo n\'est pas encore configuré. Veuillez lancer le seeder.');
        }

        // Connexion manuelle sans mot de passe
        Auth::login($demoUser);

        // Définir l'école courante en session (requis par vos middlewares)
        session(['current_school_id' => $demoUser->school_id]);

        return redirect()->route('app.dashboard')
            ->with('success', '🎉 Bienvenue dans la version de démonstration de SchoolManager !');
    }
}
