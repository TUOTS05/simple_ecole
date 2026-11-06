<?php

namespace App\Models\Traits;

use App\Models\School;
use Illuminate\Database\Eloquent\Builder;

/**
 * Cloisonnement automatique d'un modèle par école.
 */
trait BelongsToSchool
{
    /**
     * Filtre toutes les lectures sur l'école de l'utilisateur connecté et renseigne school_id à la
     * création.
     */
    protected static function bootBelongsToSchool()
    {
        // 1. FILTRAGE AUTOMATIQUE (Lecture)
        // Dès qu'on appelle Student::all(), ça ajoute automatiquement WHERE school_id = X
        static::addGlobalScope('school', function (Builder $builder) {
            if (auth()->check() && auth()->user()->school_id) {
                $builder->where($builder->getModel()->getTable().'.school_id', auth()->user()->school_id);
            }
        });

        // 2. ASSIGNATION AUTOMATIQUE (Écriture)
        // Dès qu'on crée un Student, ça met automatiquement le school_id de l'utilisateur connecté
        static::creating(function ($model) {
            if (auth()->check() && auth()->user()->school_id && ! $model->school_id) {
                $model->school_id = auth()->user()->school_id;
            }
        });
    }

    // Relation de base
    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
