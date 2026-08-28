<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExtraVehicle extends Model
{
    protected $fillable = [
        'school_id', 'plate_number', 'capacity', 'driver_name', 'driver_phone', 'assistant_name', 'status',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function routes(): HasMany
    {
        return $this->hasMany(ExtraRoute::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ExtraTransportAssignment::class);
    }

    public function occupiedSeatsCount(): int
    {
        return $this->assignments()->count();
    }

    public function hasAvailableCapacity(): bool
    {
        return $this->occupiedSeatsCount() < $this->capacity;
    }
}
