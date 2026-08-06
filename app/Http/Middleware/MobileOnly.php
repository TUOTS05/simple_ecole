<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MobileOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        if ($user && $user->isSuperAdmin()) {
            abort(403, 'Accès non autorisé pour les super administrateurs');
        }
        
        return $next($request);
    }
}