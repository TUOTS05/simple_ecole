<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportCard extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'school_year_id',
        'school_class_id',
        'period',
        'month',
        'quarter',
        'average',
        'rank',
        'total_students',
        'teacher_comment',
        'director_comment',
        'director_signed',
        'parent_signed',
        'created_by',
    ];

    protected $casts = [
        'average' => 'decimal:2',
        'director_signed' => 'boolean',
        'parent_signed' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    // public function grades(): HasMany
    // {
    //     return $this->hasMany(Grade::class);
    // }

        public function grades()
    {
        return $this->hasMany(Grade::class, 'student_id', 'student_id')
            ->where('school_year_id', $this->school_year_id)
            ->where('period', $this->period)
            ->where(function($query) {
                if ($this->period === 'mensuel') {
                    $query->where('month', $this->month);
                } else {
                    $query->where('quarter', $this->quarter);
                }
            });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}