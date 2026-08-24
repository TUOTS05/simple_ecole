<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanteenPayment extends Model
{
    protected $fillable = [
        'school_id', 'canteen_subscription_id', 'canteen_installment_id',
        'amount', 'payment_date', 'payment_method', 'payment_type',
        'reference', 'received_by', 'notes', 'receipt_path'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CanteenSubscription::class, 'canteen_subscription_id');
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(CanteenInstallment::class, 'canteen_installment_id');
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // Boot : après création, modification ou suppression d'un paiement, recalculer
    // automatiquement paid_amount/remaining_amount/status sur l'abonnement lié
    // (même principe que Payment::booted() pour Enrollment::recalculateFees()).
    protected static function booted()
    {
        static::created(function ($payment) {
            $payment->subscription?->recalculateAmounts();
        });

        static::updated(function ($payment) {
            $payment->subscription?->recalculateAmounts();
        });

        static::deleted(function ($payment) {
            $payment->subscription?->recalculateAmounts();
        });
    }
}