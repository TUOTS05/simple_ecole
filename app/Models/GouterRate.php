<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GouterRate extends Model
{
    protected $fillable = [
        'school_id',
        'school_year_id',
        'school_class_id',
        'total_amount',
        'payment_modality',
        'number_of_installments',
        'installment_amount',
        'description',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'number_of_installments' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(GouterSubscription::class);
    }
}
