<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'gender',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isSchoolAdmin(): bool
    {
        return $this->role === 'school_admin';
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isParent(): bool
    {
        // strtolower et trim garantissent que 'Parent', ' parent ' ou 'PARENT' seront reconnus comme 'parent'
        return strtolower(trim($this->role ?? '')) === 'parent';
    }

    public function isAccountant(): bool
    {
        return strtolower(trim($this->role ?? '')) === 'accountant';
    }

    public function dashboardRouteName(): string
    {
        return match ($this->role) {
            'super_admin' => 'superadmin.dashboard',
            'teacher' => 'teacher.dashboard',
            'parent' => 'parent.dashboard',
            // Le personnel comptable n'a accès qu'aux inscriptions et paiements : pas de tableau
            // de bord général à sa disposition, on l'envoie directement sur les inscriptions.
            'accountant' => 'app.enrollments.index',
            default => 'app.dashboard',
        };
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Les élèves dont cet utilisateur est parent (via la table pivot)
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'parent_student', 'parent_id', 'student_id')
            ->withPivot('school_id')
            ->withTimestamps();
    }

    /**
     * Les écoles où cet utilisateur est parent
     */
    public function parentSchools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'parent_student', 'parent_id', 'school_id')
            ->withTimestamps();
    }

    /**
     * Les messages envoyés par cet utilisateur (parent)
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Relation avec les assignations de classes (pour les enseignants)
     * ⚠️ IMPORTANT : Vérifiez si votre clé étrangère s'appelle 'teacher_id' ou 'user_id'
     * dans votre table d'assignation (ex: teacher_assignments).
     */
    /**
     * Relation avec les assignations de classes (pour les enseignants)
     */
    public function teacherAssignments()
    {
        // ✅ Changement de 'teacher_id' en 'user_id'
        return $this->hasMany(TeacherAssignment::class, 'user_id');
    }
}
