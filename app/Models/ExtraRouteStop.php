<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtraRouteStop extends Model
{
    protected $fillable = [
        'extra_route_id', 'label', 'order', 'pickup_time',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(ExtraRoute::class, 'extra_route_id');
    }
}
