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
        return $this->belongsToMany(Student::class, 'student_school_class', 'school_class_id', 'student_id')
                    ->withPivot('school_year_id')
                    ->withTimestamps();
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
        $activeSchoolYearId = SchoolYear::where('school_id', $this->school_id)
            ->where('is_active', true)
            ->value('id');

        return $this->belongsToMany(User::class, 'teacher_assignments', 'school_class_id', 'user_id')
            ->withPivot('is_main_teacher', 'school_year_id')
            ->wherePivot('school_year_id', $activeSchoolYearId);
    }


    public function teacher()
    {
        return $this->belongsTo(\App\Models\Teacher::class, 'teacher_id');
    }

    public function getNextLevel(): ?string
    {
        // Ordre logique des niveaux dans votre établissement
        $levels = ['PS', 'MS', 'GS', 'CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2'];
        $currentIndex = array_search($this->level, $levels);
        
        if ($currentIndex !== false && $currentIndex < count($levels) - 1) {
            return $levels[$currentIndex + 1]; // Retourne le niveau suivant
        }
        
        return null; // Fin du cycle (ex: après CM2)
    }

    public function getNextClassForSchoolYear($schoolYearId): ?SchoolClass
    {
        $nextLevel = $this->getNextLevel();
        if (!$nextLevel) return null;

        return SchoolClass::where('school_id', $this->school_id)
                        ->where('level', $nextLevel)
                        ->first();
    }
}
