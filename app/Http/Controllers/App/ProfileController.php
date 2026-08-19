<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * 1. Afficher le formulaire de modification du profil
     */
    public function edit()
    {
        $user = auth()->user();
        $schoolId = session('current_school_id') ?? $user->school_id;
        $school = School::find($schoolId);

        return view('app.profile.edit', compact('user', 'school'));
    }

    /**
     * 2. Mettre à jour les informations (Utilisateur + École)
     */
    public function update(Request $request)
    {
        $user = auth()->user();
        $schoolId = session('current_school_id') ?? $user->school_id;
        $school = School::find($schoolId);

        // ✅ CORRECTION ICI : Préciser que la colonne unique est 'email', pas 'user_email'
        $validatedUser = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'user_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'user_phone' => 'nullable|string|max:20',
        ]);

        // Validation des données de l'ÉCOLE
        $validatedSchool = $request->validate([
            'school_name' => 'required|string|max:255',
            'school_type' => 'required|in:maternelle,primaire,both',
            'school_address' => 'nullable|string|max:500',
            'school_email' => 'nullable|email|max:255',
            'school_phone' => 'nullable|string|max:20',
        ]);

        // Mise à jour de l'Utilisateur
        $user->update([
            'first_name' => $validatedUser['first_name'],
            'last_name' => $validatedUser['last_name'],
            'email' => $validatedUser['user_email'], // Le mappage reste correct ici
            'phone' => $validatedUser['user_phone'],
        ]);

        // Mise à jour de l'École
        if ($school) {
            $school->update([
                'name' => $validatedSchool['school_name'],
                'school_type' => $validatedSchool['school_type'],
                'address' => $validatedSchool['school_address'],
                'email' => $validatedSchool['school_email'],
                'phone' => $validatedSchool['school_phone'],
            ]);
        }

        return back()->with('success', '✅ Les informations du profil et de l\'établissement ont été mises à jour avec succès.');
    }

    /**
     * 3. Mettre à jour le mot de passe (formulaire intégré à la page de profil)
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(6)],
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', '✅ Le mot de passe a été modifié avec succès !');
    }
}