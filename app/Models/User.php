<?php

namespace App\Models;

use App\Support\Permissions;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
            'permissions' => 'array',
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

    public function isCanteenManager(): bool
    {
        return strtolower(trim($this->role ?? '')) === 'canteen_manager';
    }

    public function isTransportManager(): bool
    {
        return strtolower(trim($this->role ?? '')) === 'transport_manager';
    }

    public function isSecretary(): bool
    {
        return strtolower(trim($this->role ?? '')) === 'secretary';
    }

    /** Membre du personnel géré depuis l'écran « Personnel » (hors directeur). */
    public function isStaff(): bool
    {
        return array_key_exists(strtolower(trim($this->role ?? '')), Permissions::ROLES);
    }

    /**
     * Droits effectifs : tous pour le directeur, la liste personnalisée si le directeur en a
     * défini une, sinon les droits par défaut du rôle.
     *
     * @return list<string>
     */
    public function effectivePermissions(): array
    {
        if ($this->isSchoolAdmin()) {
            return [...Permissions::all(), Permissions::STAFF_MANAGE];
        }

        if (! $this->isStaff()) {
            return [];
        }

        return is_array($this->permissions)
            ? Permissions::sanitize($this->permissions)
            : Permissions::defaultsFor(strtolower(trim($this->role)));
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->effectivePermissions(), true);
    }

    public function hasCustomPermissions(): bool
    {
        return is_array($this->permissions);
    }

    /** Groupes de crèche auxquels ce membre du personnel est affecté. */
    public function careStaffAssignments(): HasMany
    {
        return $this->hasMany(CareStaffAssignment::class);
    }

    /**
     * Groupes de crèche accessibles : tous ceux de l'école pour le directeur, pour qui a le droit
     * « Gérer la crèche » et pour qui consulte toutes les présences (accès historique au pointage
     * crèche) ; sinon uniquement ceux auxquels il est affecté (auxiliaire, accueil).
     *
     * @return list<int>
     */
    public function accessibleCrecheClassIds(): array
    {
        if (! $this->school_id) {
            return [];
        }

        $query = SchoolClass::where('school_id', $this->school_id)->where('cycle', 'creche');

        if (! $this->hasPermission('creche.manage') && ! $this->hasPermission('attendances.view')) {
            $query->whereIn('id', $this->careStaffAssignments()->select('school_class_id'));
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function canAccessCrecheClass(int $classId): bool
    {
        return in_array($classId, $this->accessibleCrecheClassIds(), true);
    }

    public function getRoleLabelAttribute(): string
    {
        return Permissions::roleLabel($this->role);
    }

    public function dashboardRouteName(): string
    {
        return match ($this->role) {
            'super_admin' => 'superadmin.dashboard',
            'teacher' => 'teacher.dashboard',
            'parent' => 'parent.dashboard',
            'school_admin' => 'app.dashboard',
            default => $this->staffHomeRouteName(),
        };
    }

    /**
     * Personnel administratif : premier écran autorisé (comptable → inscriptions, responsable
     * cantine → présences extras, ...), ou son profil s'il n'a aucun droit.
     */
    private function staffHomeRouteName(): string
    {
        foreach (Permissions::HOME_ROUTES as $permission => $route) {
            if ($this->hasPermission($permission)) {
                return $route;
            }
        }

        return 'profile.edit';
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
