<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ExtraVehicle extends Model
{
    protected $fillable = [
        'school_id', 'plate_number', 'capacity', 'driver_name', 'driver_phone', 'assistant_name', 'status',
        'tracking_token', 'last_latitude', 'last_longitude', 'last_location_at',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'last_latitude' => 'decimal:7',
        'last_longitude' => 'decimal:7',
        'last_location_at' => 'datetime',
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

    public function locations(): HasMany
    {
        return $this->hasMany(ExtraVehicleLocation::class);
    }

    public function occupiedSeatsCount(): int
    {
        return $this->assignments()->count();
    }

    public function hasAvailableCapacity(): bool
    {
        return $this->occupiedSeatsCount() < $this->capacity;
    }

    /**
     * Génère un jeton de suivi (URL /track/{token}) s'il n'en existe pas déjà un.
     * Les chauffeurs n'ont pas de compte utilisateur : ce jeton opaque tient lieu
     * d'accès à la page de partage de position.
     */
    public function ensureTrackingToken(): string
    {
        if (! $this->tracking_token) {
            $this->tracking_token = Str::random(40);
            $this->save();
        }

        return $this->tracking_token;
    }

    /**
     * Une position vieille de plus de 10 minutes est considérée obsolète
     * (chauffeur ayant fermé la page, batterie déchargée, zone sans réseau...).
     */
    public function hasStaleLocation(): bool
    {
        return ! $this->last_location_at || $this->last_location_at->lt(now()->subMinutes(10));
    }
}
