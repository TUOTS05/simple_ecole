<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\User;
use App\Models\SubscriptionRequest;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

    // ==========================================
    // NOUVELLES MÉTHODES POUR LA DEMANDE DE COMPTE
    // ==========================================

    /**
     * Affiche le formulaire de demande de compte
     */
    public function showRequestForm()
    {
        $plans = SubscriptionPlan::where('is_active', true)->get();
        return view('auth.request-account', compact('plans'));
    }

    /**
     * Traite la soumission du formulaire de demande de compte
     */
    // public function storeRequest(Request $request)
    // {
    //     $validated = $request->validate([
    //         'school_name' => 'required|string|max:255',
    //         'school_phone' => 'required|string|max:50',
    //         'school_address' => 'required|string|max:255',
    //         'director_name' => 'required|string|max:255',
    //         'director_email' => 'required|email|unique:users,email',
    //         'plan_id' => 'required|exists:subscription_plans,id',
    //     ]);

    //     // 1. Créer l'école avec le statut 'pending' (en attente)
    //     $school = School::create([
    //         'name' => $validated['school_name'],
    //         'slug' => Str::slug($validated['school_name']) . '-' . Str::random(4),
    //         'phone' => $validated['school_phone'],
    //         'address' => $validated['school_address'],
    //         'status' => 'pending',
    //         'is_active' => false,
    //     ]);

    //     // 2. Créer l'utilisateur Directeur
    //     $directorNames = explode(' ', trim($validated['director_name']));
    //     $firstName = $directorNames[0] ?? 'Directeur';
    //     $lastName = isset($directorNames[1]) ? implode(' ', array_slice($directorNames, 1)) : 'École';

    //     User::create([
    //         'school_id' => $school->id,
    //         'first_name' => $firstName,
    //         'last_name' => $lastName,
    //         'email' => $validated['director_email'],
    //         'password' => Hash::make('Temporaire123!'), // Mot de passe provisoire
    //         'role' => 'school_admin',
    //         'email_verified_at' => now(),
    //     ]);

    //     // 3. Créer la demande d'abonnement liée à cette nouvelle école
    //     SubscriptionRequest::create([
    //         'school_id' => $school->id,
    //         'plan_id' => $validated['plan_id'],
    //         'duration' => 'yearly',
    //         'status' => 'pending',
    //     ]);

    //     return redirect()->route('landing')
    //         ->with('success', '✅ Votre demande de création d\'école a été envoyée avec succès ! Le Super Administrateur vous contactera sous 24h avec vos identifiants.');
    // }

        /**
     * Traite la soumission du formulaire de demande de compte
     */
    public function storeRequest(Request $request)
    {
        // 1. Validation stricte
        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'school_phone' => 'required|string|max:50',
            'school_address' => 'required|string|max:255',
            'director_name' => 'required|string|max:255',
            'director_email' => 'required|email|unique:users,email',
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        try {
            // 2. Créer l'école (Vérifiez que 'name', 'slug', 'phone', 'address', 'status', 'is_active' sont dans le $fillable du modèle School)
            $school = School::create([
                'name' => $validated['school_name'],
                'email' => $validated['director_email'],
                'slug' => \Illuminate\Support\Str::slug($validated['school_name']) . '-' . \Illuminate\Support\Str::random(4),
                'phone' => $validated['school_phone'],
                'address' => $validated['school_address'],
                'status' => 'pending',
                'is_active' => false,
            ]);

            // 3. Créer l'utilisateur Directeur
            $directorNames = explode(' ', trim($validated['director_name']));
            $firstName = $directorNames[0] ?? 'Directeur';
            $lastName = isset($directorNames[1]) ? implode(' ', array_slice($directorNames, 1)) : 'École';

            \App\Models\User::create([
                'school_id' => $school->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $validated['director_email'],
                'password' => \Illuminate\Support\Facades\Hash::make('Temporaire123!'),
                'role' => 'school_admin',
                'email_verified_at' => now(),
            ]);

            // 4. Créer la demande d'abonnement
            \App\Models\SubscriptionRequest::create([
                'school_id' => $school->id,
                'plan_id' => $validated['plan_id'],
                'duration' => 'yearly',
                'status' => 'pending',
            ]);

            return redirect()->route('landing')
                ->with('success', '✅ Votre demande a été envoyée avec succès ! Nous vous contacterons sous 24h.');

        } catch (\Exception $e) {
            // En cas d'erreur, on loggue et on renvoie l'utilisateur avec le message d'erreur exact
            \Illuminate\Support\Facades\Log::error('Erreur création demande compte: ' . $e->getMessage());
            
            return back()->withInput()->withErrors(['error' => 'Une erreur est survenue : ' . $e->getMessage()]);
        }
    }
}