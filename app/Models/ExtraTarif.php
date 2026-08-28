<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExtraTarif extends Model
{
    protected $fillable = [
        'extra_id', 'school_year_id', 'school_class_id',
        'amount', 'periods_count', 'start_period', 'end_period', 'due_day', 'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'periods_count' => 'integer',
        'due_day' => 'integer',
    ];

    public function extra(): BelongsTo
    {
        return $this->belongsTo(Extra::class);
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
        return $this->hasMany(ExtraSubscription::class);
    }

    public function getTotalAmountAttribute()
    {
        return $this->periods_count ? $this->amount * $this->periods_count : $this->amount;
    }
}
