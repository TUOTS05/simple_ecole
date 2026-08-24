<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grade extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id',
        'student_id',
        'subject_id',
        'school_class_id',
        'school_year_id',
        'period',
        'month',
        'quarter',
        'score',
        'max_score',
        'coefficient_used',
        'remarks',
        'marked_by',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    // Calculer la note sur 20
    public function getScoreOutOf20Attribute(): float
    {
        if ($this->max_score === null || (float) $this->max_score == 0.0) {
            return 0;
        }
        if ($this->max_score == 20) {
            return $this->score;
        }
        return ($this->score / $this->max_score) * 20;
    }
}