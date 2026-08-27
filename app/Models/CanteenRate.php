<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CanteenRate extends Model
{
    protected $fillable = [
        'school_id', 'school_year_id', 'school_class_id',
        'monthly_rate', 'months_count', 'start_month', 'end_month', 'description',
    ];

    protected $casts = [
        'monthly_rate' => 'decimal:2',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(CanteenSubscription::class, 'canteen_rate_id');
    }

    // Calcul du total annuel
    public function getTotalAmountAttribute()
    {
        return $this->monthly_rate * $this->months_count;
    }
}
