<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_name',
        'user_role',
        'action',
        'description',
        'ip_address',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i',
    ];

    /**
     * Méthode utilitaire pour enregistrer une action facilement
     */
    
        /**
     * Méthode utilitaire pour enregistrer une action facilement
     */
    public static function logAction(string $action, string $description, ?string $userName = null, ?string $userRole = null)
    {
        $user = auth()->user();
        
        // ✅ Fallback robuste : essaie 'name', puis 'email', puis 'Système'
        $finalUserName = $userName ?? ($user ? ($user->name ?? $user->email ?? 'Utilisateur') : 'Système');
        
        // ✅ Fallback robuste pour le rôle
        $finalUserRole = $userRole ?? ($user ? ($user->role ?? 'user') : 'system');

        return self::create([
            'user_name' => $finalUserName,
            'user_role' => $finalUserRole,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
        ]);
    }
}