<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    /** Capacité appliquée quand le champ est laissé vide (colonne NOT NULL, défaut 25). */
    public const DEFAULT_CAPACITY = 25;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'level',
        'cycle',
        'capacity',
        // Groupe de crèche
        'age_min_months',
        'age_max_months',
        'room',
        'responsible_user_id',
        'opening_time',
        'closing_time',
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

    /** Responsable du groupe de crèche. */
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /** Personnel de crèche affecté à ce groupe (auxiliaires, accueil, responsable). */
    public function careStaffAssignments(): HasMany
    {
        return $this->hasMany(CareStaffAssignment::class, 'school_class_id');
    }

    public function careStaff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'care_staff_assignments', 'school_class_id', 'user_id')
            ->withPivot('is_referent')
            ->withTimestamps();
    }

    public function scopeCreche($query)
    {
        return $query->where('cycle', 'creche');
    }

    // Helpers
    public function isCreche(): bool
    {
        return $this->cycle === 'creche';
    }

    /** Heure d'ouverture du groupe, sinon celle de la crèche. */
    public function effectiveOpeningTime(): string
    {
        return substr($this->opening_time ?: (string) $this->school?->setting('creche.opening_time'), 0, 5);
    }

    /** Heure de fermeture du groupe, sinon celle de la crèche (base du retard de récupération). */
    public function effectiveClosingTime(): string
    {
        return substr($this->closing_time ?: (string) $this->school?->setting('creche.closing_time'), 0, 5);
    }

    /**
     * Le groupe refuse-t-il un nouvel enfant ? Seulement pour la crèche, et seulement si l'école
     * a activé le contrôle de capacité (Crèche > Paramètres).
     */
    public function isFullFor(?int $exceptStudentId = null): bool
    {
        if (! $this->isCreche() || ! $this->capacity || ! $this->school?->setting('creche.enforce_capacity')) {
            return false;
        }

        $count = $this->students()
            ->when($exceptStudentId, fn ($q) => $q->where('students.id', '!=', $exceptStudentId))
            ->count();

        return $count >= $this->capacity;
    }

    public function getCycleLabelAttribute(): string
    {
        return match ($this->cycle) {
            'creche' => '🍼 Crèche',
            'maternelle' => '🧒 Maternelle',
            default => '📚 Primaire',
        };
    }

    /** Classes Tailwind du badge de cycle. */
    public function getCycleBadgeClassAttribute(): string
    {
        return match ($this->cycle) {
            'creche' => 'bg-pink-100 text-pink-800',
            'maternelle' => 'bg-purple-100 text-purple-800',
            default => 'bg-blue-100 text-blue-800',
        };
    }

    public function ageRangeLabel(): ?string
    {
        if ($this->age_min_months === null && $this->age_max_months === null) {
            return null;
        }

        return ($this->age_min_months ?? 0).' – '.($this->age_max_months !== null ? $this->age_max_months : '…').' mois';
    }

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
        if (! $this->capacity) {
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
        return $this->belongsTo(Teacher::class, 'teacher_id');
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
        if (! $nextLevel) {
            return null;
        }

        return SchoolClass::where('school_id', $this->school_id)
            ->where('level', $nextLevel)
            ->first();
    }
}
