<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtraOnlinePayment extends Model
{
    protected $fillable = [
        'school_id', 'extra_subscription_id', 'transaction_id', 'amount', 'status',
        'payment_token', 'initiated_by', 'extra_payment_id', 'completed_at', 'gateway_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(ExtraSubscription::class, 'extra_subscription_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(ExtraPayment::class, 'extra_payment_id');
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }
}
