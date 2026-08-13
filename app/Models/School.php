<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes; 

class School extends Model
{
    use SoftDeletes; 

    protected $fillable = [
        'name', 'slug', 'logo', 'settings', 'status', 'school_type',
        'subscription_plan', 'subscription_start_date', 'subscription_end_date', 'max_students',
        'email', 'phone', 'address', 'sms_enabled',
        'orange_sms_api_url', 'orange_sms_client_id', 'orange_sms_client_secret', 
        'orange_sms_sender_name', 'sms_absence_template',
        // ✅ Ajouts pour l'essai gratuit et le SaaS
        'plan', 'trial_ends_at', 'is_active', 'type', 'subscription_ends_at', 'email',
    ];

    protected $casts = [
        'settings' => 'array',
        'subscription_start_date' => 'date',
        'subscription_end_date' => 'date',
        'sms_enabled' => 'boolean',
        // ✅ Ajouts pour que les dates d'essai fonctionnent avec isFuture()
        'trial_ends_at' => 'date',
        'subscription_ends_at' => 'date',
        'is_active' => 'boolean',
    ];

    // Relations
    public function users(): HasMany { return $this->hasMany(User::class); }
    public function students(): HasMany { return $this->hasMany(Student::class); }
    public function classes(): HasMany { return $this->hasMany(SchoolClass::class); }
    public function attendances(): HasMany { return $this->hasMany(Attendance::class); }
    public function schoolYears(): HasMany { return $this->hasMany(SchoolYear::class); }

    // ==========================================
    // ✅ HELPERS POUR L'ESSAI GRATUIT (CEUX QUI MANQUAIENT)
    // ==========================================
    
    public function isTrialActive(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function trialDaysRemaining(): int
    {
        if (!$this->isTrialActive()) {
            return 0;
        }
        return (int) now()->diffInDays($this->trial_ends_at, false);
    }

    public function hasActiveSubscription(): bool
    {
        // Essai actif OU abonnement payant actif (supporte les deux noms de colonnes possibles)
        return $this->isTrialActive() 
            || ($this->subscription_ends_at && $this->subscription_ends_at->isFuture())
            || ($this->subscription_end_date && $this->subscription_end_date->isFuture());
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

        return match($this->school_type) {
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
        return $this->subscription_end_date && Carbon::today()->greaterThan($this->subscription_end_date);
    }

    public function isActive(): bool
    {
        if (!$this->subscription_plan) return false;
        if (!$this->subscription_start_date || !$this->subscription_end_date) return false;
        if ($this->isExpired()) return false;
        
        return $this->status === 'active';
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