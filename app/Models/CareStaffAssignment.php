<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Affectation d'un membre du personnel de crèche à un groupe (classe de cycle « creche »).
 * Un auxiliaire ne voit et ne saisit que les enfants des groupes auxquels il est affecté.
 */
class CareStaffAssignment extends Model
{
    protected $fillable = [
        'school_id',
        'school_class_id',
        'user_id',
        'is_referent',
    ];

    protected $casts = [
        'is_referent' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
