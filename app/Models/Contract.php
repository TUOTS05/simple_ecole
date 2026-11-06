<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Contrat d'abonnement d'une école à une offre, sur une période.
 */
class Contract extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id',
        'contract_number',
        'type',
        'parent_contract_id',
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

    /**
     * Contrat d'origine d'un avenant ou d'un renouvellement.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'parent_contract_id');
    }

    /**
     * Avenants et renouvellements établis à partir de ce contrat.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Contract::class, 'parent_contract_id');
    }

    /**
     * Libellé du type de document.
     */
    public function typeLabel(): string
    {
        return match ($this->type) {
            'avenant' => 'Avenant',
            'renouvellement' => 'Renouvellement',
            default => 'Contrat',
        };
    }
}
