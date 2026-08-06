<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon; // Ajouté pour la gestion des dates
use Illuminate\Database\Eloquent\SoftDeletes; 

class School extends Model
{
    use SoftDeletes; 
        protected $fillable = [
        'name', 'slug', 'logo', 'settings', 'status', 'school_type',
        'subscription_plan', 'subscription_start_date', 'subscription_end_date', 'max_students',
        'email', 'phone', 'address','sms_enabled',
    'orange_sms_api_url',
    'orange_sms_client_id',
    'orange_sms_client_secret',
    'orange_sms_sender_name',
    'sms_absence_template',
    ];

    protected $casts = [
        'settings' => 'array',
        // NOUVEAUX CASTS POUR LES DATES
        'subscription_start_date' => 'date',
        'subscription_end_date' => 'date',
        'sms_enabled' => 'boolean',
    ];

    // Relations
    public function users(): HasMany { return $this->hasMany(User::class); }
    public function students(): HasMany { return $this->hasMany(Student::class); }
    public function classes(): HasMany { return $this->hasMany(SchoolClass::class); }
    public function attendances(): HasMany { return $this->hasMany(Attendance::class); }

    // Helpers pour le type d'école
    public function isMaternelle(): bool
    {
        return $this->school_type === 'maternelle';
    }

    public function isPrimaire(): bool
    {
        return $this->school_type === 'primaire';
    }

    public function isBoth(): bool
    {
        return $this->school_type === 'both';
    }

    // Retourne les niveaux autorisés selon le type d'école
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
    // NOUVEAUX HELPERS POUR LA GESTION SAAS
    // ==========================================

    /**
     * Vérifie si l'abonnement de l'école est expiré
     */
    public function isExpired(): bool
    {
        return $this->subscription_end_date && Carbon::today()->greaterThan($this->subscription_end_date);
    }

    /**
     * Vérifie si l'école est active (statut actif ET non expiré)
     */
        /**
     * Vérifie si l'école est active (statut actif ET abonnement valide)
     */
    public function isActive(): bool
    {
        // L'école doit avoir un plan d'abonnement
        if (!$this->subscription_plan) {
            return false;
        }

        // L'école doit avoir une date de début et une date de fin
        if (!$this->subscription_start_date || !$this->subscription_end_date) {
            return false;
        }

        // L'école ne doit pas être expirée
        if ($this->isExpired()) {
            return false;
        }

        // Le statut doit être 'active'
        return $this->status === 'active';
    }

    /**
     * Accesseur pour obtenir un badge HTML de statut (à utiliser en Blade avec {!! $school->status_badge !!})
     */
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