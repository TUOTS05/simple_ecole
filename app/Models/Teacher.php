<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends User
{
    use HasFactory;

    // On force le modèle à utiliser la table users
    protected $table = 'users';

    // Scope global pour ne récupérer que les enseignants
    protected static function booted()
    {
        static::addGlobalScope('teacher', function ($query) {
            $query->where('role', 'teacher');
        });
    }

    // Relation avec les assignations de classes
    public function assignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class, 'user_id');
    }

    // Helper pour savoir si c'est un titulaire dans une classe donnée
    public function isMainTeacherIn($classId)
    {
        return $this->assignments()
            ->where('school_class_id', $classId)
            ->where('is_main_teacher', true)
            ->exists();
    }
}
