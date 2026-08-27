<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'student_photo',
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

    // Méthode pour générer le matricule
    public static function generateMatricule(int $schoolId): string
    {
        $school = School::find($schoolId);
        $year = date('Y');
        $schoolCode = strtoupper(substr($school->slug, 0, 3));

        $lastStudent = self::where('school_id', $schoolId)
            ->where('matricule', 'like', "{$year}-{$schoolCode}-%")
            ->orderBy('matricule', 'desc')
            ->first();

        if ($lastStudent) {
            $parts = explode('-', $lastStudent->matricule);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $number = str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        return "{$year}-{$schoolCode}-{$number}";
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
}
