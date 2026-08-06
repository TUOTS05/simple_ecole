<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    protected $fillable = [
        'student_id',
        'school_id',
        'school_year_id',
        'school_class_id',
        'status',
        'enrollment_date',
        'registration_fee_paid',
        'tuition_fee_total',
        'tuition_fee_paid',
        'tuition_fee_remaining',
        'notes',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'registration_fee_paid' => 'boolean',
        'tuition_fee_total' => 'decimal:2',
        'tuition_fee_paid' => 'decimal:2',
        'tuition_fee_remaining' => 'decimal:2',
    ];

    // Relations
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

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

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Helpers
    public function isReserved(): bool
    {
        return $this->status === 'reserved';
    }

    public function isEnrolled(): bool
    {
        return $this->status === 'enrolled';
    }

    public function isWithdrawn(): bool
    {
        return $this->status === 'withdrawn';
    }

    public function isFullyPaid(): bool
    {
        return $this->registration_fee_paid && $this->tuition_fee_remaining == 0;
    }

    // Recalculer les montants après un paiement
    public function recalculateFees(): void
    {
        $totalPaid = $this->payments()
            ->where('payment_type', 'tuition')
            ->sum('amount');

        $this->update([
            'tuition_fee_paid' => $totalPaid,
            'tuition_fee_remaining' => $this->tuition_fee_total - $totalPaid,
        ]);

        // Si tout est payé, passer le statut à "enrolled"
        if ($this->registration_fee_paid && $this->tuition_fee_remaining == 0) {
            $this->update(['status' => 'enrolled']);
        }
    }

    public function studentInstallments()
    {
        return $this->hasMany(StudentInstallment::class);
    }

        public function canteenSubscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CanteenSubscription::class);
    }

    public function currentCanteenSubscription()
    {
        return $this->hasOne(CanteenSubscription::class)
            ->where('school_year_id', SchoolYear::where('is_active', true)->value('id'))
            ->where('status', 'active');
    }
}