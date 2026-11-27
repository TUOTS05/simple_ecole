<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dossier crèche d'un enfant (profil de prise en charge). La fiche élève commune reste la source
 * des informations d'identité et de famille ; ce profil n'ajoute que le suivi petite enfance.
 */
class ChildCareProfile extends Model
{
    /** Régimes de garde proposés. */
    public const CARE_ARRANGEMENTS = [
        'full_time' => 'Temps plein',
        'part_time' => 'Temps partiel',
        'occasional' => 'Occasionnel',
    ];

    /** Champs saisis depuis le formulaire, par rubrique (l'ordre sert à l'affichage). */
    public const SECTIONS = [
        'feeding' => [
            'milk_type' => 'Type de lait',
            'usual_quantity' => 'Quantité habituelle',
            'feeding_frequency' => 'Fréquence',
            'introduced_foods' => 'Aliments introduits',
            'forbidden_foods' => 'Aliments interdits',
            'allergies' => 'Allergies signalées',
            'food_preferences' => 'Préférences alimentaires',
        ],
        'sleep' => [
            'usual_sleep_time' => 'Heure habituelle',
            'usual_sleep_duration' => 'Durée habituelle',
            'sleep_ritual' => 'Rituel',
            'comforter' => 'Doudou',
            'sleep_notes' => 'Observations',
        ],
        'hygiene' => [
            'changing_habits' => 'Habitudes de change',
            'specific_products' => 'Produits spécifiques',
            'cream' => 'Crème',
            'hygiene_notes' => 'Autres informations',
        ],
    ];

    protected $fillable = [
        'entry_date', 'care_arrangement', 'referent_user_id',
        'milk_type', 'breastfed', 'formula_fed', 'usual_quantity', 'feeding_frequency',
        'introduced_foods', 'forbidden_foods', 'allergies', 'food_preferences',
        'usual_sleep_time', 'usual_sleep_duration', 'sleep_ritual', 'comforter', 'pacifier', 'sleep_notes',
        'changing_habits', 'specific_products', 'cream', 'hygiene_notes',
        'particularities',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'breastfed' => 'boolean',
        'formula_fed' => 'boolean',
        'pacifier' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function referent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referent_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getCareArrangementLabelAttribute(): ?string
    {
        return self::CARE_ARRANGEMENTS[$this->care_arrangement] ?? null;
    }

    /** Points de vigilance à montrer en premier au personnel (allergies, interdits). */
    public function hasAlerts(): bool
    {
        return filled($this->allergies) || filled($this->forbidden_foods);
    }
}
