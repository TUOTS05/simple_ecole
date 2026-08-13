<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    // Les champs qui peuvent être remplis en masse
    protected $fillable = [
        'school_id',
        'plan_id',
        'plan_name',
        'start_date',
        'end_date',
        'amount',
        'status',
    ];

    // Conversion automatique des dates
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Relations
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }
}