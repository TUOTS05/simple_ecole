<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Acceptation d'une version du règlement intérieur par un parent (date et adresse IP).
 */
class RegulationAcceptance extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'ip_address', 'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function regulation(): BelongsTo
    {
        return $this->belongsTo(SchoolRegulation::class, 'school_regulation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
