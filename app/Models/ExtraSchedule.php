<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtraSchedule extends Model
{
    protected $fillable = [
        'extra_id', 'day_of_week', 'start_time', 'end_time',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
    ];

    public function extra(): BelongsTo
    {
        return $this->belongsTo(Extra::class);
    }

    public function getDayLabelAttribute(): string
    {
        $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

        return $days[$this->day_of_week] ?? '';
    }
}
