<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'logo', 'settings', 'status', 'school_type',
        'subscription_plan', 'subscription_start_date', 'subscription_end_date', 'max_students', 'max_users',
        'email', 'phone', 'address', 'sms_enabled',
        'orange_sms_api_url', 'orange_sms_client_id', 'orange_sms_client_secret',
        'orange_sms_sender_name', 'sms_absence_template',
        // ✅ Ajouts pour l'essai gratuit et le SaaS
        'trial_ends_at', 'is_active', 'type',
    ];

    protected $casts = [
        'settings' => 'array',
        'subscription_start_date' => 'date',
        'subscription_end_date' => 'date',
        'sms_enabled' => 'boolean',
        // ✅ Ajouts pour que les dates d'essai fonctionnent avec isFuture()
        'trial_ends_at' => 'date',
        'is_active' => 'boolean',
    ];

    // Relations
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function schoolYears(): HasMany
    {
        return $this->hasMany(SchoolYear::class);
    }

    // ==========================================
    // ✅ HELPERS POUR L'ESSAI GRATUIT (CEUX QUI MANQUAIENT)
    // ==========================================

    public function isTrialActive(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function trialDaysRemaining(): int
    {
        if (! $this->isTrialActive()) {
            return 0;
        }

        return (int) now()->diffInDays($this->trial_ends_at, false);
    }

    // ==========================================
    // HELPERS POUR LE TYPE D'ÉCOLE
    // ==========================================

    public function isMaternelle(): bool
    {
        return in_array($this->school_type, ['maternelle', 'both']);
    }

    public function isPrimaire(): bool
    {
        return in_array($this->school_type, ['primaire', 'both']);
    }

    public function isBoth(): bool
    {
        return $this->school_type === 'both';
    }

    public function getAllowedLevels(): array
    {
        $maternelleLevels = ['TPS', 'PS', 'MS', 'GS'];
        $primaireLevels = ['CP', 'CE1', 'CE2', 'CM1', 'CM2'];

        return match ($this->school_type) {
            'maternelle' => $maternelleLevels,
            'primaire' => $primaireLevels,
            'both' => array_merge($maternelleLevels, $primaireLevels),
            default => [],
        };
    }

    // ==========================================
    // HELPERS POUR LA GESTION SAAS
    // ==========================================

    public function isExpired(): bool
    {
        // Abonnement payant en cours de validité ?
        if ($this->subscription_end_date && Carbon::today()->lessThanOrEqualTo($this->subscription_end_date)) {
            return false;
        }

        // Sinon, essai gratuit en cours de validité ?
        if ($this->isTrialActive()) {
            return false;
        }

        // Ni abonnement ni essai actif : expiré, sauf si aucune échéance n'a jamais été fixée
        // (écoles créées avant la mise en place du système d'essai/abonnement).
        return $this->subscription_end_date !== null || $this->trial_ends_at !== null;
    }

    /**
     * Nombre de comptes utilisateurs (personnel) de l'école : tous les rôles sauf 'parent',
     * qui n'est pas un siège du plan mais un compte lié aux élèves.
     */
    public function activeUserCount(): int
    {
        return $this->users()->where('role', '!=', 'parent')->count();
    }

    public function hasReachedUserLimit(): bool
    {
        return $this->max_users !== null && $this->activeUserCount() >= $this->max_users;
    }

    public function getStatusBadgeAttribute(): string
    {
        if ($this->isExpired() || $this->status === 'expired') {
            return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Expiré</span>';
        }
        if ($this->status === 'suspended') {
            return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Suspendu</span>';
        }

        return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Actif</span>';
    }
}
