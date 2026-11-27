<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use App\Support\SchoolNumbering;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use BelongsToSchool;
    use SoftDeletes;

    // TOUS les champs du formulaire doivent être ici pour être enregistrés
    protected $fillable = [
        'admission_number', // ✅ AJOUTEZ CETTE LIGNE SI ELLE MANQUE
        'matricule',
        'first_name',
        'last_name',
        'gender',
        'section',
        'birth_date',
        'status',
        'large_family',
        'staff_child',
        'religion',
        'admission_date',
        'photo', // chemin sur le disque privé (la colonne s'appelle « photo », pas « student_photo »)
        'receipt_number',
        'father_name',
        'father_phone',
        'father_occupation',
        'father_photo',
        'mother_name',
        'mother_phone',
        'mother_occupation',
        'mother_photo',
        'guardian_type',
        'guardian_name',
        'guardian_relation',
        'guardian_email',
        'guardian_photo',
        'guardian_phone',
        'guardian_occupation',
        'guardian_address',
        'current_address',
        'permanent_address',
        'previous_school',
        'remarks',
        'dietary_restrictions',
        'id_card_path', // ✅ Ajouté précédemment
        'documents',
        // ... vos autres colonnes
    ];

    protected $casts = [
        'birth_date' => 'date',
        'admission_date' => 'date',
        'large_family' => 'boolean',
        'staff_child' => 'boolean',
        'documents' => 'array',
    ];

    // Relations
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'student_school_class', 'student_id', 'school_class_id')
            ->withPivot('school_year_id')
            ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function crecheAttendances(): HasMany
    {
        return $this->hasMany(CrecheAttendance::class);
    }

    /** Dossier crèche (profil de prise en charge). */
    public function careProfile(): HasOne
    {
        return $this->hasOne(ChildCareProfile::class);
    }

    /** Personnes autorisées ou interdites à récupérer l'enfant. */
    public function authorizedPickups(): HasMany
    {
        return $this->hasMany(AuthorizedPickup::class);
    }

    /** Groupe de crèche actuel de l'enfant, s'il en a un. */
    public function crecheClass(): ?SchoolClass
    {
        return $this->classes->firstWhere('cycle', 'creche');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(ReportCard::class);
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_student', 'student_id', 'parent_id')
            ->withPivot('school_id')
            ->withTimestamps();
    }

    // Générer automatiquement le matricule avant la création
    protected static function booted()
    {
        static::creating(function ($student) {
            if (empty($student->matricule)) {
                $student->matricule = self::generateMatricule($student->school_id);
            }
        });
    }

    // Génère le matricule selon le format choisi par l'école (Personnalisation > Matricules)
    public static function generateMatricule(int $schoolId): string
    {
        return SchoolNumbering::next(School::findOrFail($schoolId), 'matricule');
    }

    public static function generateAdmissionNumber(int $schoolId): string
    {
        return SchoolNumbering::next(School::findOrFail($schoolId), 'admission');
    }

    public function scopeByMatricule($query, string $matricule)
    {
        return $query->where('matricule', $matricule);
    }

    public function canteenSubscriptions(): HasMany
    {
        return $this->hasMany(CanteenSubscription::class);
    }

    public function currentCanteenSubscription()
    {
        return $this->hasOne(CanteenSubscription::class)
            ->where('school_year_id', SchoolYear::where('is_active', true)->value('id'))
            ->where('status', 'active');
    }

    public function gouterSubscriptions(): HasMany
    {
        return $this->hasMany(GouterSubscription::class);
    }

    public function currentGouterSubscription()
    {
        return $this->hasOne(GouterSubscription::class)
            ->where('school_year_id', SchoolYear::where('is_active', true)->value('id'))
            ->where('status', 'active');
    }

    public function extraSubscriptions(): HasMany
    {
        return $this->hasMany(ExtraSubscription::class);
    }
}
