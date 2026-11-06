<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Menu du jour d'un extra de restauration.
 */
class ExtraMenu extends Model
{
    protected $fillable = [
        'school_id', 'extra_id', 'date', 'entree', 'plat', 'dessert', 'gouter',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function extra(): BelongsTo
    {
        return $this->belongsTo(Extra::class);
    }
}
