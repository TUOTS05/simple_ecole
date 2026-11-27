<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Personne autorisée — ou explicitement interdite — à récupérer un enfant de la crèche.
 * Une autorisation peut être limitée dans le temps (dates de début et de fin).
 */
class AuthorizedPickup extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'authorized' => 'Autorisée',
        'forbidden' => 'Interdite',
    ];

    public const RELATIONSHIPS = [
        'Mère', 'Père', 'Tuteur / tutrice', 'Grand-mère', 'Grand-père', 'Tante', 'Oncle',
        'Frère / sœur majeur(e)', 'Nounou', 'Chauffeur', 'Voisin(e)', 'Autre',
    ];

    protected $fillable = [
        'first_name', 'last_name', 'phone', 'relationship', 'status', 'starts_on', 'ends_on', 'comment',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /** Autorisée ce jour-là : statut « autorisée » et date dans la période de validité. */
    public function isAuthorizedOn(?CarbonInterface $date = null): bool
    {
        $date = ($date ?? now())->copy()->startOfDay();

        return $this->status === 'authorized'
            && (! $this->starts_on || $this->starts_on->lte($date))
            && (! $this->ends_on || $this->ends_on->gte($date));
    }

    /** Libellé de l'état à une date : Autorisée, Interdite, Pas encore valable, Expirée. */
    public function stateLabel(?CarbonInterface $date = null): string
    {
        $date = ($date ?? now())->copy()->startOfDay();

        if ($this->status === 'forbidden') {
            return 'Interdite';
        }
        if ($this->starts_on && $this->starts_on->gt($date)) {
            return 'Pas encore valable';
        }
        if ($this->ends_on && $this->ends_on->lt($date)) {
            return 'Expirée';
        }

        return 'Autorisée';
    }

    /** Résumé pour le journal d'audit (pas de photo ni de commentaire). */
    public function auditSnapshot(): array
    {
        return [
            'name' => $this->full_name,
            'relationship' => $this->relationship,
            'phone' => $this->phone,
            'status' => $this->status,
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
        ];
    }
}
