<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SchoolOnboardingController extends Controller
{
    /**
     * Afficher la page de validation du contrat
     */
    public function showContract(string $token)
    {
        $school = School::where('validation_token', $token)->firstOrFail();

        // Si le contrat est déjà validé, rediriger vers le login
        if ($school->contract_validated_at) {
            return redirect()->route('login')->with('success', '✅ Votre contrat a déjà été validé. Vous pouvez vous connecter.');
        }

        return view('auth.validate-contract', compact('school'));
    }

    /**
     * Valider le contrat et activer l'école
     */
    public function validateContract(Request $request, string $token)
    {
        $school = School::where('validation_token', $token)->firstOrFail();

        // Vérifier que le contrat n'a pas déjà été validé
        if ($school->contract_validated_at) {
            return redirect()->route('login')->with('success', '✅ Votre contrat a déjà été validé. Vous pouvez vous connecter.');
        }

        // Valider que l'utilisateur a bien coché la case d'acceptation
        $request->validate([
            'accept_terms' => 'required|accepted',
        ], [
            'accept_terms.required' => 'Vous devez accepter les conditions d\'utilisation pour continuer.',
            'accept_terms.accepted' => 'Vous devez accepter les conditions d\'utilisation pour continuer.',
        ]);

        // Mettre à jour l'école
        $school->update([
            'status' => 'active',
            'contract_validated_at' => Carbon::now(),
            'validation_token' => null, // Supprimer le token après utilisation (sécurité)
        ]);

        return redirect()->route('login')->with('success', '🎉 Contrat validé avec succès ! Vous pouvez maintenant vous connecter avec vos identifiants.');
    }
}