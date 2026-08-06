<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'level',
        'cycle',
        'capacity',
        'total_tuition',
        'registration_fee',
        'payment_modality',
        'number_of_installments',
        'installment_amount',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'total_tuition' => 'decimal:2',
        'registration_fee' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relations
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_school_class', 'school_class_id', 'student_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    // Helpers
    public function isMaternelle(): bool
    {
        return $this->cycle === 'maternelle';
    }

    public function isPrimaire(): bool
    {
        return $this->cycle === 'primaire';
    }

    public function getFillRateAttribute(): ?float
    {
        if (!$this->capacity) {
            return null;
        }
        return ($this->students()->count() / $this->capacity) * 100;
    }

        public function teacherAssignments()
    {
        return $this->hasMany(TeacherAssignment::class, 'school_class_id');
    }

    public function teachers()
    {
        return $this->belongsToMany(User::class, 'teacher_assignments', 'school_class_id', 'user_id')
                    ->withPivot('is_main_teacher', 'school_year_id')
                    ->wherePivot('school_year_id', function($query) {
                        // Optionnel : filtrer par l'année en cours si nécessaire
                    });
    }


}