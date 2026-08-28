<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Extra extends Model
{
    protected $fillable = [
        'school_id', 'extra_category_id', 'code', 'name', 'description', 'photo_path',
        'status', 'target_audience', 'billing_type', 'capacity', 'responsible_id',
        'conditions', 'start_date', 'end_date',
        'location', 'daycare_closing_time', 'overage_rate_per_minute',
        'destination', 'registration_deadline', 'includes_transport', 'requires_parental_authorization',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'overage_rate_per_minute' => 'decimal:2',
        'registration_deadline' => 'date',
        'includes_transport' => 'boolean',
        'requires_parental_authorization' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExtraCategory::class, 'extra_category_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function tarifs(): HasMany
    {
        return $this->hasMany(ExtraTarif::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ExtraSubscription::class);
    }

    public function menus(): HasMany
    {
        return $this->hasMany(ExtraMenu::class);
    }

    public function routes(): HasMany
    {
        return $this->hasMany(ExtraRoute::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ExtraSchedule::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isRecurring(): bool
    {
        return $this->billing_type === 'recurring';
    }

    /**
     * Nombre d'inscriptions qui occupent une place (demande en cours ou active).
     */
    public function occupiedSeatsCount(): int
    {
        return $this->subscriptions()
            ->whereIn('status', ['requested', 'pending', 'validated', 'active'])
            ->count();
    }

    public function hasAvailableCapacity(): bool
    {
        if ($this->capacity === null) {
            return true;
        }

        return $this->occupiedSeatsCount() < $this->capacity;
    }

    /**
     * Vrai si aucune date limite d'inscription n'est définie, ou si elle n'est pas
     * encore passée (utilisé notamment pour les sorties scolaires, spec §23).
     */
    public function isRegistrationOpen(): bool
    {
        return ! $this->registration_deadline || now()->toDateString() <= $this->registration_deadline->toDateString();
    }
}
