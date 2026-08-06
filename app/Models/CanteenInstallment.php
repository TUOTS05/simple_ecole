<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CanteenInstallment extends Model
{
    protected $fillable = [
        'canteen_subscription_id', 'month', 'amount', 
        'paid_amount', 'due_date', 'status'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CanteenSubscription::class, 'canteen_subscription_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CanteenPayment::class, 'canteen_installment_id');
    }

    public function getIsOverdueAttribute()
    {
        return $this->status !== 'paid' && now()->gt($this->due_date);
    }

    public function getRemainingAmountAttribute()
    {
        return $this->amount - $this->paid_amount;
    }

    // Formatage du mois pour affichage
    public function getMonthLabelAttribute()
    {
        return \Carbon\Carbon::parse($this->month . '-01')->translatedFormat('F Y');
    }
}