<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Observation du développement de l'enfant dans un domaine (motricité, langage, ...).
 * Ce n'est pas une note scolaire : seulement observé / en cours / à stimuler.
 */
class DevelopmentObservation extends Model
{
    use SoftDeletes;

    public const LEVELS = [
        'observed' => 'Observé',
        'in_progress' => 'En cours',
        'to_stimulate' => 'À stimuler',
    ];

    protected $fillable = ['observed_on', 'domain', 'level', 'notes'];

    protected $casts = [
        'observed_on' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function getLevelLabelAttribute(): string
    {
        return self::LEVELS[$this->level] ?? (string) $this->level;
    }

    public function getLevelBadgeClassAttribute(): string
    {
        return match ($this->level) {
            'observed' => 'bg-green-100 text-green-800',
            'in_progress' => 'bg-blue-100 text-blue-800',
            default => 'bg-amber-100 text-amber-800',
        };
    }
}
