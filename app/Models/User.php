<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'school_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'gender', 
        'role',
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

    public function dashboardRouteName(): string
    {
        return match ($this->role) {
            'super_admin' => 'superadmin.dashboard',
            'teacher' => 'teacher.dashboard',
            'parent' => 'parent.dashboard',
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
    public function children(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'parent_student', 'parent_id', 'student_id')
            ->withPivot('school_id')
            ->withTimestamps();
    }

    /**
     * Les écoles où cet utilisateur est parent
     */
    public function parentSchools(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(School::class, 'parent_student', 'parent_id', 'school_id')
            ->withTimestamps();
    }

    /**
     * Les messages envoyés par cet utilisateur (parent)
     */
    public function sentMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
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
        return $this->hasMany(\App\Models\TeacherAssignment::class, 'user_id');
    }
}
