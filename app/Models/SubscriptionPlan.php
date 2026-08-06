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
        'max_teachers',
        'max_classes',
        'monthly_price',
        'yearly_price',
        'is_active',
        'features',
        'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
    ];

    /**
     * Scope pour récupérer uniquement les plans actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Accesseur pour formater le prix mensuel
     */
    public function getFormattedMonthlyPriceAttribute()
    {
        return number_format($this->monthly_price, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Accesseur pour formater le prix annuel
     */
    public function getFormattedYearlyPriceAttribute()
    {
        return number_format($this->yearly_price, 0, ',', ' ') . ' FCFA';
    }
}