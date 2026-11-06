<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Validation de l'adresse e-mail depuis le lien reçu.
 */
class VerifyEmailController extends Controller
{
    /**
     * Marque l'adresse e-mail de l'utilisateur connecté comme vérifiée.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route($request->user()->dashboardRouteName(), absolute: false).'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended(route($request->user()->dashboardRouteName(), absolute: false).'?verified=1');
    }
}
