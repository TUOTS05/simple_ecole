<?php

namespace App\Models;

use App\Support\SchoolSettings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;

class School extends Model
{
    use SoftDeletes;

    /** Structures qu'une école peut activer (une ou plusieurs), dans l'ordre du parcours de l'enfant. */
    public const STRUCTURES = [
        'creche' => 'Crèche',
        'maternelle' => 'Maternelle',
        'primaire' => 'Primaire',
    ];

    public const MATERNELLE_LEVELS = ['TPS', 'PS', 'MS', 'GS'];

    // « CP » seul n'est plus proposé : il était choisi à la place de CP1/CP2 et les matières
    // (configurées en CP1) ne correspondaient plus. Il reste accepté pour les classes existantes.
    public const PRIMAIRE_LEVELS = ['CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2'];

    public const PRIMAIRE_LEGACY_LEVELS = ['CP'];

    protected static function booted(): void
    {
        // school_type et structures restent cohérents quel que soit le champ modifié :
        // l'inscription publique d'une école envoie encore school_type.
        static::saving(function (School $school) {
            if ($school->isDirty('structures') && ! empty($school->structures)) {
                $school->structures = self::normalizeStructures($school->structures);
                $school->school_type = self::typeForStructures($school->structures);
            } elseif ($school->isDirty('school_type') || empty($school->structures)) {
                $school->structures = self::structuresForType($school->school_type);
            }
        });
    }

    protected $fillable = [
        'name', 'slug', 'logo', 'settings', 'status', 'school_type', 'structures',
        'subscription_plan', 'subscription_start_date', 'subscription_end_date', 'max_students', 'max_users',
        'email', 'phone', 'address', 'sms_enabled',
        'orange_sms_api_url', 'orange_sms_client_id', 'orange_sms_client_secret',
        'orange_sms_sender_name', 'sms_absence_template',
        // ✅ Ajouts pour l'essai gratuit et le SaaS
        'trial_ends_at', 'is_active', 'type',
    ];

    protected $casts = [
        'settings' => 'array',
        'structures' => 'array',
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

    public function pendingSubscriptionRequest(): HasOne
    {
        return $this->hasOne(SubscriptionRequest::class)->where('status', 'pending')->latestOfMany();
    }

    // ==========================================
    // LIBELLÉS D'AFFICHAGE (FR)
    // ==========================================

    /**
     * Statut "réel" affiché au super admin : une école au statut technique
     * 'active' mais encore en période d'essai apparaît comme "Essai".
     */
    public function getDisplayStatusAttribute(): string
    {
        if ($this->status === 'suspended') {
            return 'suspended';
        }
        if ($this->status === 'expired' || $this->isExpired()) {
            return 'expired';
        }
        if ($this->isTrialActive()) {
            return 'trial';
        }

        return 'active';
    }

    public function getDisplayStatusLabelAttribute(): string
    {
        return [
            'active' => 'Actif',
            'trial' => 'Essai',
            'suspended' => 'Suspendu',
            'expired' => 'Expiré',
        ][$this->display_status] ?? ucfirst((string) $this->status);
    }

    public function getSchoolTypeLabelAttribute(): string
    {
        return implode(' + ', array_map(fn ($s) => self::STRUCTURES[$s], $this->structureList()));
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
    // STRUCTURES (CRÈCHE / MATERNELLE / PRIMAIRE)
    // ==========================================

    /** Structures actives de l'école, dans l'ordre de STRUCTURES. */
    public function structureList(): array
    {
        return self::normalizeStructures(
            is_array($this->structures) && $this->structures ? $this->structures : self::structuresForType($this->school_type)
        );
    }

    public function hasStructure(string $structure): bool
    {
        return in_array($structure, $this->structureList(), true);
    }

    public function isMaternelle(): bool
    {
        return $this->hasStructure('maternelle');
    }

    public function isPrimaire(): bool
    {
        return $this->hasStructure('primaire');
    }

    public function isBoth(): bool
    {
        return $this->isMaternelle() && $this->isPrimaire();
    }

    /** La structure crèche est active (seule ou avec d'autres). */
    public function isCreche(): bool
    {
        return $this->hasStructure('creche');
    }

    /** Crèche uniquement : pas de bulletins ni de matières. */
    public function isCrecheOnly(): bool
    {
        return $this->structureList() === ['creche'];
    }

    /** Groupes d'âge de la crèche (paramétrables dans Crèche > Paramètres). */
    public function crecheLevels(): array
    {
        return array_values(array_filter((array) $this->setting('creche.levels')));
    }

    /** Niveaux proposés à la création d'une classe, par cycle actif. */
    public function levelsByCycle(): array
    {
        $levels = [
            'creche' => $this->crecheLevels(),
            'maternelle' => self::MATERNELLE_LEVELS,
            'primaire' => self::PRIMAIRE_LEVELS,
        ];

        return array_intersect_key($levels, array_flip($this->structureList()));
    }

    /** Niveaux acceptés pour un cycle (les proposés + les anciens encore portés par des classes). */
    public function acceptedLevelsFor(string $cycle): array
    {
        return match ($cycle) {
            'creche' => $this->crecheLevels(),
            'maternelle' => self::MATERNELLE_LEVELS,
            'primaire' => [...self::PRIMAIRE_LEGACY_LEVELS, ...self::PRIMAIRE_LEVELS],
            default => [],
        };
    }

    /** Tous les niveaux acceptés par l'école (frais par niveau, ...). */
    public function getAllowedLevels(): array
    {
        $levels = [];
        foreach ($this->structureList() as $cycle) {
            $levels = [...$levels, ...$this->acceptedLevelsFor($cycle)];
        }

        return $levels;
    }

    /**
     * Règles de validation des structures d'un formulaire d'école. L'ancien champ school_type
     * reste accepté (inscription publique d'une école, anciens clients).
     */
    public static function structureRules(): array
    {
        return [
            'structures' => 'required_without:school_type|array|min:1',
            'structures.*' => ['string', Rule::in(array_keys(self::STRUCTURES))],
            'school_type' => 'nullable|in:maternelle,primaire,both,creche',
        ];
    }

    public static function structureMessages(): array
    {
        return [
            'structures.required_without' => 'Cochez au moins une structure (crèche, maternelle ou primaire).',
            'structures.min' => 'Cochez au moins une structure (crèche, maternelle ou primaire).',
            'structures.*.in' => 'Structure inconnue.',
        ];
    }

    /** Structures choisies dans un formulaire validé par structureRules(). */
    public static function structuresFromInput(array $validated): array
    {
        return ! empty($validated['structures'])
            ? self::normalizeStructures($validated['structures'])
            : self::structuresForType($validated['school_type'] ?? null);
    }

    public static function normalizeStructures(array $structures): array
    {
        return array_values(array_intersect(array_keys(self::STRUCTURES), $structures));
    }

    /** Traduction de l'ancien school_type en liste de structures. */
    public static function structuresForType(?string $type): array
    {
        return match ($type) {
            'maternelle' => ['maternelle'],
            'primaire' => ['primaire'],
            'creche' => ['creche'],
            default => ['maternelle', 'primaire'],
        };
    }

    /** school_type conservé pour compatibilité ; 'multi' quand la crèche s'ajoute à l'école. */
    public static function typeForStructures(array $structures): string
    {
        return match (self::normalizeStructures($structures)) {
            ['creche'] => 'creche',
            ['maternelle'] => 'maternelle',
            ['primaire'] => 'primaire',
            ['maternelle', 'primaire'] => 'both',
            default => 'multi',
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

    /** Réglage de personnalisation de l'école (voir App\Support\SchoolSettings). */
    public function setting(string $key): mixed
    {
        return SchoolSettings::get($this, $key);
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
