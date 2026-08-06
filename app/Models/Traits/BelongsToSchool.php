<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToSchool
{
    protected static function bootBelongsToSchool()
    {
        // 1. FILTRAGE AUTOMATIQUE (Lecture)
        // Dès qu'on appelle Student::all(), ça ajoute automatiquement WHERE school_id = X
        static::addGlobalScope('school', function (Builder $builder) {
            if (auth()->check() && auth()->user()->school_id) {
                $builder->where($builder->getModel()->getTable() . '.school_id', auth()->user()->school_id);
            }
        });

        // 2. ASSIGNATION AUTOMATIQUE (Écriture)
        // Dès qu'on crée un Student, ça met automatiquement le school_id de l'utilisateur connecté
        static::creating(function ($model) {
            if (auth()->check() && auth()->user()->school_id && !$model->school_id) {
                $model->school_id = auth()->user()->school_id;
            }
        });
    }

    // Relation de base
    public function school()
    {
        return $this->belongsTo(\App\Models\School::class);
    }
}