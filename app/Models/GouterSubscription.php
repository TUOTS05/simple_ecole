<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GouterSubscription extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'school_year_id',
        'gouter_rate_id',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function gouterRate(): BelongsTo
    {
        return $this->belongsTo(GouterRate::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(GouterInstallment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(GouterPayment::class);
    }

    public function getPaymentRateAttribute(): float
    {
        $total = (float) $this->total_amount;

        return $total > 0 ? round(((float) $this->paid_amount / $total) * 100, 1) : 0;
    }

    /**
     * Recalcule paid_amount, remaining_amount et status à partir de la somme réelle
     * des paiements liés à cet abonnement. Source de vérité unique : appelée
     * automatiquement par les hooks de GouterPayment (created/updated/deleted),
     * mais peut aussi être appelée manuellement (ex : après modification de total_amount).
     */
    public function recalculateAmounts(): void
    {
        $totalPaid = $this->payments()->sum('amount');

        $this->paid_amount = $totalPaid;
        $this->remaining_amount = $this->total_amount - $totalPaid;
        $this->status = $this->remaining_amount <= 0 ? 'paid' : 'active';

        $this->save();
    }
}
