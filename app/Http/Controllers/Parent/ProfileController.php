<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
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
        return view('parent.profile.edit', [
            'user' => auth()->user()
        ]);
    }

    /**
     * 2. Mettre à jour les informations du profil
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20', // Adaptez le nom si votre colonne s'appelle 'telephone' ou autre
        ]);

        $user->update($validated);

        return back()->with('success', '✅ Vos informations personnelles ont été mises à jour avec succès.');
    }

    /**
     * 3. Afficher le formulaire de modification du mot de passe
     */
    public function editPassword()
    {
        $user = auth()->user();
        return view('parent.profile.password', compact('user'));
    }

    /**
     * 4. Mettre à jour le mot de passe
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(6)],
        ]);

        $user = auth()->user();

        // Vérifier que l'ancien mot de passe est correct
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.']);
        }

        // Mettre à jour avec le nouveau mot de passe
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', '✅ Votre mot de passe a été modifié avec succès !');
    }
}