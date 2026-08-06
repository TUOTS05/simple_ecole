<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $fillable = [
        'school_id',
        'school_year_id',
        'name',
        'cycle',
        'level',
        'coefficient',
        'max_score',
        'is_active',
    ];

        protected $casts = [
        'is_active' => 'boolean',
        'coefficient' => 'float',
        'max_score' => 'float',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }
}