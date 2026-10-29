<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Échéance d'une inscription à un extra (unique ou mensuelle).
 */
class ExtraInstallment extends Model
{
    protected $fillable = [
        'extra_subscription_id', 'period', 'amount', 'paid_amount', 'due_date', 'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(ExtraSubscription::class, 'extra_subscription_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ExtraPayment::class, 'extra_installment_id');
    }

    public function getIsOverdueAttribute()
    {
        return $this->status !== 'paid' && now()->gt($this->due_date);
    }

    public function getRemainingAmountAttribute()
    {
        return $this->amount - $this->paid_amount;
    }

    /**
     * Libellé de la période : « Paiement unique » ou le mois en toutes lettres.
     */
    public function getPeriodLabelAttribute()
    {
        if ($this->period === 'unique') {
            return 'Paiement unique';
        }

        return Carbon::parse($this->period.'-01')->translatedFormat('F Y');
    }
}
