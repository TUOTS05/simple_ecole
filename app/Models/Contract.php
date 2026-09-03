<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use SoftDeletes;

    // ✅ Colonnes qui existent réellement dans votre table 'contracts'
    protected $fillable = [
        'school_id',
        'contract_number',
        'plan_name',      // <-- Texte (ex: "Premium") au lieu de plan_id
        'amount',
        'start_date',
        'end_date',
        'max_students',
        'max_users',
        'status',
        'pdf_path',
        'signed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'signed_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
