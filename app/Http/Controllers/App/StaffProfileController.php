<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Profil personnel d'un membre du personnel administratif (accessible quelle que soit sa fonction).
 */
class StaffProfileController extends Controller
{
    /**
     * Profil personnel des membres du personnel administratif : uniquement ses propres informations
     * (pas les données de l'école, réservées à ProfileController/school_admin).
     */
    public function edit()
    {
        return view('app.staff-profile.edit', ['user' => auth()->user()]);
    }

    /**
     * Met à jour le nom et l'adresse e-mail du membre connecté.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'gender' => 'required|in:M,F',
        ]);

        $user->update($validated);

        return back()->with('success', '✅ Vos informations personnelles ont été mises à jour avec succès.');
    }

    /**
     * Change le mot de passe après vérification du mot de passe actuel.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(6)],
        ]);

        $user = auth()->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success_password', '✅ Votre mot de passe a été modifié avec succès !');
    }
}
