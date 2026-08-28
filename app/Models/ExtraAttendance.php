<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtraAttendance extends Model
{
    protected $fillable = [
        'extra_subscription_id', 'date', 'status', 'checked_in_at', 'checked_out_at',
        'overage_minutes', 'overage_amount', 'overage_billed_at', 'notes', 'recorded_by',
    ];

    protected $casts = [
        'date' => 'date',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'overage_amount' => 'decimal:2',
        'overage_billed_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(ExtraSubscription::class, 'extra_subscription_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Calcule et enregistre le dépassement horaire par rapport à l'heure de
     * fermeture définie sur l'extra (garderie), si applicable.
     */
    public function computeOverage(): void
    {
        $extra = $this->subscription->extra;

        if (! $this->checked_out_at || ! $extra->daycare_closing_time) {
            return;
        }

        $closing = Carbon::parse($this->date->format('Y-m-d').' '.$extra->daycare_closing_time);
        $minutes = $closing->diffInMinutes($this->checked_out_at, false);

        if ($minutes > 0) {
            $this->overage_minutes = $minutes;
            $this->overage_amount = $extra->overage_rate_per_minute ? $minutes * $extra->overage_rate_per_minute : null;
        } else {
            $this->overage_minutes = null;
            $this->overage_amount = null;
        }
    }
}
