<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtraTransportAssignment extends Model
{
    protected $fillable = [
        'extra_subscription_id', 'extra_route_id', 'extra_route_stop_id', 'extra_vehicle_id',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(ExtraSubscription::class, 'extra_subscription_id');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(ExtraRoute::class, 'extra_route_id');
    }

    public function stop(): BelongsTo
    {
        return $this->belongsTo(ExtraRouteStop::class, 'extra_route_stop_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(ExtraVehicle::class, 'extra_vehicle_id');
    }
}
