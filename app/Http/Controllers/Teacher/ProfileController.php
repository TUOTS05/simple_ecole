<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return view('teacher.profile.index', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'gender' => 'required|in:M,F',
        ]);

        $user->update($validated);

        return back()->with('success', '✅ Vos informations ont été mises à jour avec succès.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => $validated['password'],
        ]);

        return back()->with('success_password', '✅ Votre mot de passe a été changé avec succès.');
    }
}
