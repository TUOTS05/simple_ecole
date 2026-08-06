<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Afficher la page des paramètres
     */
    public function settings()
    {
        return view('profile.settings', ['user' => auth()->user()]);
    }

    /**
     * Mettre à jour les paramètres
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'language' => 'nullable|string|max:10',
            'theme' => 'nullable|string|max:20',
            'timezone' => 'nullable|string|max:50',
            'notify_email' => 'nullable|boolean',
            'notify_messages' => 'nullable|boolean',
            'notify_payments' => 'nullable|boolean',
        ]);

        $user = auth()->user();
        $user->update([
            'language' => $validated['language'] ?? 'fr',
            'theme' => $validated['theme'] ?? 'light',
            'timezone' => $validated['timezone'] ?? 'Africa/Abidjan',
            'notify_email' => $request->has('notify_email'),
            'notify_messages' => $request->has('notify_messages'),
            'notify_payments' => $request->has('notify_payments'),
        ]);

        return redirect()->route('profile.settings')
            ->with('success', 'Paramètres mis à jour avec succès !');
    }
}
