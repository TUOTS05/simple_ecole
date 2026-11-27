<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Humeur et message de l'auxiliaire publiés aux parents pour une journée. */
class CareDailySummary extends Model
{
    public const MOODS = [
        'very_good' => ['label' => 'Très bien', 'icon' => '😄'],
        'good' => ['label' => 'Bien', 'icon' => '🙂'],
        'average' => ['label' => 'Moyen', 'icon' => '😐'],
        'difficult' => ['label' => 'Journée difficile', 'icon' => '😟'],
    ];

    protected $fillable = ['summary_date', 'mood', 'message'];

    protected $casts = [
        'summary_date' => 'date',
        'published_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function getMoodLabelAttribute(): ?string
    {
        return self::MOODS[$this->mood]['label'] ?? null;
    }

    public function getMoodIconAttribute(): ?string
    {
        return self::MOODS[$this->mood]['icon'] ?? null;
    }
}
