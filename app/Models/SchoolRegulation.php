<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Version du règlement intérieur d'une école ; chaque publication crée une nouvelle version.
 */
class SchoolRegulation extends Model
{
    protected $fillable = [
        'title', 'content', 'file_path', 'requires_acceptance',
    ];

    protected $casts = [
        'requires_acceptance' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function acceptances(): HasMany
    {
        return $this->hasMany(RegulationAcceptance::class);
    }

    /**
     * Version en vigueur (la plus récente) du règlement d'une école.
     */
    public static function currentFor(?int $schoolId): ?self
    {
        if (! $schoolId) {
            return null;
        }

        return static::where('school_id', $schoolId)->orderByDesc('version')->first();
    }

    public function isAcceptedBy(User $user): bool
    {
        return $this->acceptances()->where('user_id', $user->id)->exists();
    }

    /**
     * Vrai si ce parent doit encore accepter la version en vigueur du règlement de son école.
     */
    public static function pendingFor(User $user): ?self
    {
        $current = static::currentFor($user->school_id);

        return $current && $current->requires_acceptance && ! $current->isAcceptedBy($user) ? $current : null;
    }
}
