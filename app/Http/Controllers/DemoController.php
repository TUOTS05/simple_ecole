<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DemoController extends Controller
{
    public function login()
    {
        // Trouver l'utilisateur de démo
        $demoUser = User::where('email', 'demo@schoolmanager.com')->first();

        if (!$demoUser) {
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