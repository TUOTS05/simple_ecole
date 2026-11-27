<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Événement du journal quotidien d'un enfant (biberon, repas, sommeil, change, ...).
 * Une seule table pour tous les types ; les champs propres à un type sont dans « data » :
 *  - bottle   : quantity (ml préparés), data.consumed (ml bus), data.milk, data.preparation
 *  - meal / snack : data.menu, data.appreciation
 *  - sleep    : start_time, end_time (vide = en train de dormir), data.quality
 *  - diaper   : data.kind, data.product
 *  - hygiene  : data.kind
 *  - activity : data.category, data.title, quantity (durée en minutes)
 *  - observation / other : notes
 */
class CareEvent extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'bottle' => ['label' => 'Biberon', 'icon' => '🍼'],
        'meal' => ['label' => 'Repas', 'icon' => '🍽️'],
        'snack' => ['label' => 'Goûter', 'icon' => '🍪'],
        'sleep' => ['label' => 'Sommeil', 'icon' => '😴'],
        'diaper' => ['label' => 'Change', 'icon' => '🧷'],
        'hygiene' => ['label' => 'Hygiène', 'icon' => '🧼'],
        'activity' => ['label' => 'Activité', 'icon' => '🎨'],
        'observation' => ['label' => 'Observation', 'icon' => '📝'],
        'other' => ['label' => 'Autre', 'icon' => '📌'],
    ];

    public const APPRECIATIONS = [
        'all' => 'Tout mangé',
        'almost' => 'Presque tout',
        'half' => 'Moitié',
        'little' => 'Peu mangé',
        'refused' => 'Refusé',
    ];

    public const SLEEP_QUALITIES = [
        'very_good' => 'Très bon',
        'good' => 'Bon',
        'average' => 'Moyen',
        'difficult' => 'Difficile',
    ];

    public const DIAPER_KINDS = [
        'urine' => 'Urine',
        'stool' => 'Selles',
        'mixed' => 'Mixte',
        'other' => 'Autre',
    ];

    protected $fillable = [
        'event_type', 'event_date', 'start_time', 'end_time', 'quantity', 'unit', 'data', 'notes', 'device_id',
    ];

    protected $casts = [
        'event_date' => 'date',
        'quantity' => 'float',
        'data' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (CareEvent $event) {
            $event->uuid ??= (string) Str::uuid();
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

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->event_type]['label'] ?? (string) $this->event_type;
    }

    public function getIconAttribute(): string
    {
        return self::TYPES[$this->event_type]['icon'] ?? '•';
    }

    public function startLabel(): string
    {
        return substr((string) $this->start_time, 0, 5);
    }

    public function endLabel(): ?string
    {
        return $this->end_time ? substr((string) $this->end_time, 0, 5) : null;
    }

    public function isSleeping(): bool
    {
        return $this->event_type === 'sleep' && ! $this->end_time;
    }

    /** Durée en minutes (sommeil : fin - début ; activité : durée saisie). */
    public function durationMinutes(): ?int
    {
        if ($this->event_type === 'activity') {
            return $this->quantity ? (int) $this->quantity : null;
        }
        if (! $this->end_time) {
            return null;
        }

        [$sh, $sm] = array_map('intval', explode(':', (string) $this->start_time));
        [$eh, $em] = array_map('intval', explode(':', (string) $this->end_time));

        return max(0, ($eh * 60 + $em) - ($sh * 60 + $sm));
    }

    public static function formatDuration(?int $minutes): ?string
    {
        if ($minutes === null) {
            return null;
        }

        return $minutes >= 60 ? intdiv($minutes, 60).'h'.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT) : $minutes.' min';
    }

    public function datum(string $key): mixed
    {
        return ($this->data ?? [])[$key] ?? null;
    }

    /** Résumé lisible : « 150 ml · lait 2e âge · bu 120 ml (80 %) », « 1h45 · Bon », ... */
    public function summary(): string
    {
        $parts = match ($this->event_type) {
            'bottle' => [
                $this->quantity ? $this->formatNumber($this->quantity).' '.($this->unit ?: 'ml') : null,
                $this->datum('milk'),
                $this->datum('preparation'),
                $this->bottleConsumption(),
            ],
            'meal', 'snack' => [
                $this->datum('menu'),
                self::APPRECIATIONS[$this->datum('appreciation')] ?? null,
            ],
            'sleep' => [
                $this->isSleeping() ? 'en cours' : self::formatDuration($this->durationMinutes()),
                self::SLEEP_QUALITIES[$this->datum('quality')] ?? null,
            ],
            'diaper' => [
                self::DIAPER_KINDS[$this->datum('kind')] ?? null,
                $this->datum('product'),
            ],
            'hygiene' => [$this->datum('kind')],
            'activity' => [
                $this->datum('category'),
                $this->datum('title'),
                self::formatDuration($this->durationMinutes()),
            ],
            default => [],
        };

        return implode(' · ', array_filter($parts, fn ($part) => filled($part)));
    }

    /** « bu 120 ml (80 %) » pour un biberon dont la quantité bue est connue. */
    public function bottleConsumption(): ?string
    {
        $consumed = $this->datum('consumed');
        if ($consumed === null || $consumed === '') {
            return null;
        }

        $text = 'bu '.$this->formatNumber((float) $consumed).' '.($this->unit ?: 'ml');
        if ($this->quantity > 0) {
            $text .= ' ('.round(min(100, $consumed / $this->quantity * 100)).' %)';
        }

        return $text;
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', ' '), '0'), ',');
    }
}
