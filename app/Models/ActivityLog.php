<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'school_id',
        'user_id',
        'user_name',
        'user_role',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'meta',
        'ip_address',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i',
        'meta' => 'array',
    ];

    /**
     * Méthode utilitaire pour enregistrer une action facilement
     */
    public static function logAction(string $action, string $description, ?string $userName = null, ?string $userRole = null)
    {
        return self::write($action, $description, null, [], $userName, $userRole);
    }

    /**
     * Journal d'audit : action rattachée à l'école de l'utilisateur et à l'élément concerné
     * (ex. un enfant : ActivityLog::audit('creche.departure', '...', $student, ['pickup' => ...])).
     */
    public static function audit(string $action, string $description, ?Model $subject = null, array $meta = [])
    {
        return self::write($action, $description, $subject, $meta);
    }

    private static function write(string $action, string $description, ?Model $subject, array $meta, ?string $userName = null, ?string $userRole = null)
    {
        $user = auth()->user();

        // ✅ Fallback robuste : essaie 'name', puis 'email', puis 'Système'
        $finalUserName = $userName ?? ($user ? ($user->name ?? $user->full_name ?: $user->email ?? 'Utilisateur') : 'Système');

        // ✅ Fallback robuste pour le rôle
        $finalUserRole = $userRole ?? ($user ? ($user->role ?? 'user') : 'system');

        return self::create([
            'school_id' => $subject?->getAttribute('school_id') ?? $user?->school_id,
            'user_id' => $user?->id,
            'user_name' => $finalUserName,
            'user_role' => $finalUserRole,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'meta' => $meta ?: null,
            'ip_address' => request()->ip(),
        ]);
    }
}
