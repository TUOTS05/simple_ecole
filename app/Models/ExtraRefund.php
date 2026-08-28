<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtraRefund extends Model
{
    protected $fillable = [
        'school_id', 'extra_subscription_id', 'amount', 'reason',
        'refund_method', 'processed_by', 'processed_at', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(ExtraSubscription::class, 'extra_subscription_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
