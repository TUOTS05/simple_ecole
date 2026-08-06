<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
        public function store(LoginRequest $request)
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = auth()->user();

        if (method_exists($user, 'isParent') && $user->isParent()) {
            return redirect()->intended(route('parent.dashboard'));
        }
        // ✅ Redirection explicite selon le rôle (remplace la ligne dashboardRouteName())
        if (method_exists($user, 'isTeacher') && $user->isTeacher()) {
            return redirect()->intended(route('teacher.dashboard'));
        }


        if (method_exists($user, 'isSchoolAdmin') && $user->isSchoolAdmin()) {
            return redirect()->intended(route('app.dashboard')); // Ou 'school.dashboard' selon votre nommage
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return redirect()->intended(route('superadmin.dashboard'));
        }

        // Fallback par défaut si aucune condition n'est remplie
        return redirect()->intended(route('app.dashboard'));
    }
    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
