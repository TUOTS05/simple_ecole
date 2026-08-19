<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GouterInstallment extends Model
{
    protected $fillable = [
        'gouter_subscription_id',
        'label',
        'amount',
        'paid_amount',
        'due_date',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(GouterSubscription::class, 'gouter_subscription_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(GouterPayment::class, 'gouter_installment_id');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'paid' && $this->due_date && $this->due_date->isPast();
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->amount - (float) $this->paid_amount);
    }
}
