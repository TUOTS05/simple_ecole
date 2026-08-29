<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtraVehicleLocation extends Model
{
    protected $fillable = [
        'extra_vehicle_id', 'latitude', 'longitude', 'speed_kmh', 'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'speed_kmh' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(ExtraVehicle::class, 'extra_vehicle_id');
    }
}
