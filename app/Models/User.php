<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

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

    /**
     * Membre du personnel administratif dont les accès dépendent de sa fonction
     * (voir StaffFunction et config/school_permissions.php).
     */
    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function staffFunction(): BelongsTo
    {
        return $this->belongsTo(StaffFunction::class);
    }

    /**
     * Modules accordés à ce membre du personnel ; une fonction d'une autre école (rattachement
     * incohérent) n'accorde rien.
     */
    public function staffModules(): array
    {
        if (! $this->isStaff() || ! $this->staffFunction || $this->staffFunction->school_id !== $this->school_id) {
            return [];
        }

        return $this->staffFunction->modules();
    }

    public function hasModule(string $module): bool
    {
        return $this->isSchoolAdmin() || in_array($module, $this->staffModules(), true);
    }

    /**
     * Un membre du personnel peut-il ouvrir cette route ? Refus par défaut : seules les routes
     * des modules de sa fonction (et son profil) sont ouvertes.
     */
    public function staffCanAccessRoute(?string $routeName): bool
    {
        if (! $routeName) {
            return false;
        }

        $modules = config('school_permissions.modules');
        $patterns = config('school_permissions.always');

        foreach ($this->staffModules() as $module) {
            array_push($patterns, ...$modules[$module]['routes']);
        }

        return Str::is($patterns, $routeName);
    }

    public function dashboardRouteName(): string
    {
        if ($this->isStaff()) {
            $first = $this->staffModules()[0] ?? null;

            return $first ? config("school_permissions.modules.{$first}.home") : 'app.staff-profile.edit';
        }

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
