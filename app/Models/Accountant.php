<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Accountant extends User
{
    use HasFactory;

    // On force le modèle à utiliser la table users
    protected $table = 'users';

    // Scope global pour ne récupérer que le personnel comptable
    protected static function booted()
    {
        static::addGlobalScope('accountant', function ($query) {
            $query->where('role', 'accountant');
        });
    }
}
