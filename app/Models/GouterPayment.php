<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GouterPayment extends Model
{
    protected $fillable = [
        'school_id',
        'gouter_subscription_id',
        'gouter_installment_id',
        'amount',
        'payment_date',
        'payment_method',
        'payment_type',
        'reference',
        'received_by',
        'notes',
        'receipt_path',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(GouterSubscription::class, 'gouter_subscription_id');
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(GouterInstallment::class, 'gouter_installment_id');
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
