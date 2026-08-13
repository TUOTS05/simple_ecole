<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionRequest;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SubscriptionRequestController extends Controller
{
    public function create()
    {
        $plans = SubscriptionPlan::where('is_active', true)->get();
        return view('app.subscription.request', compact('plans'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'school_phone' => 'required|string|max:50',
            'school_address' => 'required|string|max:255',
            'director_name' => 'required|string|max:255',
            'director_email' => 'required|email|unique:users,email',
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        // 1. Créer l'école avec le statut 'pending' (en attente de validation)
        $school = School::create([
            'name' => $validated['school_name'],
            'slug' => Str::slug($validated['school_name']) . '-' . Str::random(4),
            'phone' => $validated['school_phone'],
            'address' => $validated['school_address'],
            'status' => 'pending', // ⚠️ CRUCIAL : L'école n'est pas encore active
            'is_active' => false,
        ]);

        // 2. Créer l'utilisateur Directeur lié à cette nouvelle école
        $directorNames = explode(' ', trim($validated['director_name']));
        $firstName = $directorNames[0] ?? 'Directeur';
        $lastName = isset($directorNames[1]) ? implode(' ', array_slice($directorNames, 1)) : 'École';

        User::create([
            'school_id' => $school->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $validated['director_email'],
            'password' => Hash::make('Temporaire123!'), // Mot de passe provisoire
            'role' => 'school_admin',
            'email_verified_at' => now(), // On le vérifie automatiquement pour simplifier
        ]);

        // 3. Créer la demande d'abonnement liée à cette NOUVELLE école
        SubscriptionRequest::create([
            'school_id' => $school->id,
            'plan_id' => $validated['plan_id'],
            'duration' => 'yearly', // Par défaut, ou à adapter si vous ajoutez le champ
            'status' => 'pending',
        ]);

        return redirect()->route('landing') // Retour à l'accueil
            ->with('success', '✅ Votre demande de création d\'école a été envoyée au Super Administrateur. Vous recevrez vos identifiants par email après validation.');
    }
}