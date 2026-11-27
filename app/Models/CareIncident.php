<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Incident survenu à un enfant de la crèche. Cycle : declared (déclaré, modifiable) →
 * validated (validé par la responsable, figé) ; ou cancelled (déclaration erronée, figée).
 * Après validation, seuls des suivis peuvent être ajoutés (follow_ups, jamais modifiés).
 */
class CareIncident extends Model
{
    public const TYPES = [
        'fall' => 'Chute',
        'injury' => 'Blessure',
        'unusual_behavior' => 'Comportement inhabituel',
        'reported_illness' => 'Malaise signalé',
        'other' => 'Autre',
    ];

    public const STATUSES = [
        'declared' => 'À valider',
        'validated' => 'Validé',
        'cancelled' => 'Annulé',
    ];

    protected $fillable = [
        'occurred_on', 'occurred_at', 'type', 'description', 'action_taken', 'person_informed', 'informed_at',
    ];

    protected $casts = [
        'occurred_on' => 'date',
        'informed_at' => 'datetime',
        'validated_at' => 'datetime',
        'follow_ups' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (CareIncident $incident) {
            $incident->uuid ??= (string) Str::uuid();
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function isEditable(): bool
    {
        return $this->status === 'declared';
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? (string) $this->type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    public function timeLabel(): string
    {
        return substr((string) $this->occurred_at, 0, 5);
    }

    /** Résumé pour le journal d'audit. */
    public function auditSnapshot(): array
    {
        return [
            'date' => $this->occurred_on?->toDateString(),
            'time' => $this->timeLabel(),
            'type' => $this->type,
            'description' => $this->description,
            'action_taken' => $this->action_taken,
            'person_informed' => $this->person_informed,
            'informed_at' => $this->informed_at?->format('Y-m-d H:i'),
        ];
    }
}
