<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'enrollment_id',
        'student_installment_id',
        'school_id',
        'amount',
        'payment_date',
        'payment_type',
        'payment_method',
        'reference',
        'received_by',
        'notes',
        'receipt_path',
        
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    // Relations
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // Helpers
    public function isForRegistration(): bool
    {
        return $this->payment_type === 'registration';
    }

    public function isForTuition(): bool
    {
        return $this->payment_type === 'tuition';
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', ' ') . ' FCFA';
    }

    // Boot : après création OU suppression d'un paiement, recalculer les montants de l'enrollment
    protected static function booted()
    {
        static::created(function ($payment) {
            $payment->enrollment->recalculateFees();

            // Si c'est un paiement d'inscription, marquer comme payé
            if ($payment->payment_type === 'registration') {
                $payment->enrollment->update(['registration_fee_paid' => true]);
            }
        });

        static::deleted(function ($payment) {
            $enrollment = $payment->enrollment;
            if (!$enrollment) {
                return;
            }

            $enrollment->recalculateFees();

            if ($payment->payment_type === 'registration') {
                $stillHasRegistrationPayment = $enrollment->payments()
                    ->where('payment_type', 'registration')
                    ->exists();
                $enrollment->update(['registration_fee_paid' => $stillHasRegistrationPayment]);
            }
        });
    }


    public function studentInstallment()
    {
        return $this->belongsTo(StudentInstallment::class);
    }
}