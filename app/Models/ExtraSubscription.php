<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExtraSubscription extends Model
{
    protected $fillable = [
        'school_id', 'student_id', 'extra_id', 'extra_tarif_id', 'school_year_id',
        'total_amount', 'paid_amount', 'remaining_amount', 'status',
        'requested_by', 'validated_by', 'validated_at', 'start_date', 'end_date', 'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'validated_at' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function extra(): BelongsTo
    {
        return $this->belongsTo(Extra::class);
    }

    public function extraTarif(): BelongsTo
    {
        return $this->belongsTo(ExtraTarif::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(ExtraInstallment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ExtraPayment::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ExtraAttendance::class);
    }

    public function transportAssignment()
    {
        return $this->hasOne(ExtraTransportAssignment::class);
    }

    public function getPaymentRateAttribute()
    {
        return $this->total_amount > 0
            ? round(($this->paid_amount / $this->total_amount) * 100, 1)
            : 0;
    }

    /**
     * Recalcule paid_amount, remaining_amount et status à partir de la somme réelle
     * des paiements liés à cet abonnement. Source de vérité unique : appelée
     * automatiquement par les hooks de ExtraPayment (created/updated/deleted),
     * même principe que CanteenSubscription::recalculateAmounts().
     */
    public function recalculateAmounts(): void
    {
        $totalPaid = $this->payments()->sum('amount');

        $this->paid_amount = $totalPaid;
        $this->remaining_amount = $this->total_amount - $totalPaid;

        if (! in_array($this->status, ['requested', 'pending', 'suspended', 'terminated'])) {
            $this->status = $this->remaining_amount <= 0 ? 'completed' : 'active';
        }

        $this->save();
    }

    /**
     * Répartit un montant payé sur les échéances impayées, du plus ancien au plus
     * récent (FIFO) — même logique que CanteenController@paymentsStore, centralisée
     * ici pour éviter de la dupliquer dans le contrôleur Extras.
     */
    public function allocatePayment(float $amount): void
    {
        $remaining = $amount;

        foreach ($this->installments()->orderBy('due_date', 'asc')->get() as $installment) {
            if ($remaining <= 0) {
                break;
            }

            $due = $installment->amount - $installment->paid_amount;

            if ($remaining >= $due) {
                $installment->paid_amount = $installment->amount;
                $installment->status = 'paid';
                $remaining -= $due;
            } else {
                $installment->paid_amount += $remaining;
                $installment->status = 'partial';
                $remaining = 0;
            }

            $installment->save();
        }
    }
}
