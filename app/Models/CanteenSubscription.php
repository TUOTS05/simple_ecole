<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CanteenSubscription extends Model
{
    protected $fillable = [
        'school_id', 'student_id', 'school_year_id', 'canteen_rate_id',
        'total_amount', 'paid_amount', 'remaining_amount', 'status', 'notes'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function canteenRate(): BelongsTo
    {
        return $this->belongsTo(CanteenRate::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(CanteenInstallment::class, 'canteen_subscription_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CanteenPayment::class, 'canteen_subscription_id');
    }

    public function getPaymentRateAttribute()
    {
        return $this->total_amount > 0 
            ? round(($this->paid_amount / $this->total_amount) * 100, 1) 
            : 0;
    }
}