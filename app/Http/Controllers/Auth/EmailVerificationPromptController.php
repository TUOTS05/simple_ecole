<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Invitation à vérifier son adresse e-mail.
 */
class EmailVerificationPromptController extends Controller
{
    /**
     * Affiche l'invitation à vérifier l'adresse e-mail.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended(route($request->user()->dashboardRouteName(), absolute: false))
                    : view('auth.verify-email');
    }
}
