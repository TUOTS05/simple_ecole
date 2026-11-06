<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Confirmation du mot de passe avant l'accès à une page sensible.
 */
class ConfirmablePasswordController extends Controller
{
    /**
     * Affiche l'écran de confirmation du mot de passe.
     */
    public function show(): View
    {
        return view('auth.confirm-password');
    }

    /**
     * Vérifie le mot de passe de l'utilisateur.
     */
    public function store(Request $request): RedirectResponse
    {
        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route($request->user()->dashboardRouteName(), absolute: false));
    }
}
