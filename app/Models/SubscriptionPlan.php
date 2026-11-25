<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'max_students',
        'max_users',
        'max_classes',
        'monthly_price',
        'is_active',
        'is_recommended',
        'features',
        'sort_order',
    ];

    /** Valeur utilisée pour "illimité" (élèves / utilisateurs / classes) : les champs sont obligatoires. */
    public const UNLIMITED = 99999;

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'is_recommended' => 'boolean',
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
    ];

    /**
     * Le montant annuel n'est jamais saisi : il vaut toujours 12 fois le prix
     * mensuel, recalculé automatiquement à chaque enregistrement.
     */
    protected static function booted()
    {
        static::saving(function (self $plan) {
            $plan->yearly_price = ($plan->monthly_price ?? 0) * 12;
        });
    }

    /**
     * Scope pour récupérer uniquement les plans actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Plan "Sur devis" : aucun prix fixé (0 FCFA par mois et par an).
     */
    public function isQuote(): bool
    {
        return (float) $this->monthly_price <= 0 && (float) $this->yearly_price <= 0;
    }

    /**
     * Libellé d'une limite : "illimités" au-delà du seuil UNLIMITED.
     */
    public static function formatLimit(?int $value, string $unit): string
    {
        if ($value === null || $value >= self::UNLIMITED) {
            // Pas de ucfirst()/mb_* : non multi-octets / mbstring absent en production.
            $labels = ['élèves' => 'Élèves', 'utilisateurs' => 'Utilisateurs', 'classes' => 'Classes'];

            return ($labels[$unit] ?? $unit).' illimités';
        }

        return 'Max '.number_format($value, 0, ',', ' ').' '.$unit;
    }

    /**
     * Accesseur pour formater le prix mensuel
     */
    public function getFormattedMonthlyPriceAttribute()
    {
        return number_format($this->monthly_price, 0, ',', ' ').' FCFA';
    }

    /**
     * Accesseur pour formater le prix annuel
     */
    public function getFormattedYearlyPriceAttribute()
    {
        return number_format($this->yearly_price, 0, ',', ' ').' FCFA';
    }
}
