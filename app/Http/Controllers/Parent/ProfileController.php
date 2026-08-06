<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Afficher le formulaire de modification du mot de passe
     */
    public function editPassword()
    {
        $user = auth()->user();
        return view('parent.profile.password', compact('user'));
    }

    /**
     * Mettre à jour le mot de passe
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(6)],
        ]);

        $user = auth()->user();

        // 1. Vérifier que l'ancien mot de passe est correct
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.']);
        }

        // 2. Mettre à jour avec le nouveau mot de passe
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', '✅ Votre mot de passe a été modifié avec succès !');
    }
}