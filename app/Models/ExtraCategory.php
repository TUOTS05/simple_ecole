<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catégorie d'extras (activités, sorties, services…), propre à chaque école.
 */
class ExtraCategory extends Model
{
    protected $fillable = [
        'school_id', 'name', 'description', 'icon', 'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function extras(): HasMany
    {
        return $this->hasMany(Extra::class);
    }
}
