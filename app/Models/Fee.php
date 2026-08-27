<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fee extends Model
{
    protected $fillable = [
        'school_id',
        'school_year_id',
        'level',
        'fee_type',
        'amount',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // Relations
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    // Helpers
    public function isRegistration(): bool
    {
        return $this->fee_type === 'registration';
    }

    public function isTuition(): bool
    {
        return $this->fee_type === 'tuition';
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', ' ').' FCFA';
    }
}
