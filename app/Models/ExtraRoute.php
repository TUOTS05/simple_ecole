<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Circuit de ramassage d'un extra de transport, avec ses arrêts et son véhicule.
 */
class ExtraRoute extends Model
{
    protected $fillable = [
        'extra_id', 'extra_vehicle_id', 'name',
    ];

    public function extra(): BelongsTo
    {
        return $this->belongsTo(Extra::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(ExtraVehicle::class, 'extra_vehicle_id');
    }

    public function stops(): HasMany
    {
        return $this->hasMany(ExtraRouteStop::class)->orderBy('order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ExtraTransportAssignment::class);
    }
}
