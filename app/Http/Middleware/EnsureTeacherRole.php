<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacherRole
{
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si l'utilisateur est connecté et a le rôle 'teacher'
        if (!auth()->check() || auth()->user()->role !== 'teacher') {
            abort(403, 'Accès réservé aux enseignants.');
        }

        return $next($request);
    }
}