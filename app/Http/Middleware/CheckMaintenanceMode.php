<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Bloque l'accès au site pour tout le monde sauf le Super Admin quand le
     * mode maintenance est activé dans les paramètres système. Les routes de
     * connexion/déconnexion restent accessibles pour que le Super Admin
     * puisse s'authentifier et désactiver la maintenance.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $settings = SystemSetting::getSettings();

        if (! $settings->maintenance_mode) {
            return $next($request);
        }

        $user = $request->user();
        $userRole = strtolower(trim($user->role ?? ''));

        if ($userRole === 'super_admin') {
            return $next($request);
        }

        if ($request->routeIs('login', 'logout')) {
            return $next($request);
        }

        return response()->view('errors.maintenance', [
            'message' => $settings->maintenance_message,
            'platformName' => $settings->platform_name,
        ], 503);
    }
}
