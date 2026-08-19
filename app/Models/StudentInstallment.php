<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentInstallment extends Model
{
    protected $fillable = [
        'school_id', 'enrollment_id', 'type', 'description', 
        'amount', 'paid_amount', 'due_date', 'status'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Accessor pour savoir si c'est en retard
    public function getIsOverdueAttribute()
    {
        return $this->status !== 'paid' && now()->gt($this->due_date);
    }

    /**
     * Génère l'échéancier (frais d'inscription + échéances de scolarité selon la modalité de la
     * classe) pour une inscription. Point d'entrée unique utilisé à la création d'un élève
     * (StudentController) et à la génération à la volée (PaymentController::getStudentsByClass) :
     * ces deux appelants ne doivent jamais recalculer cette logique séparément, sous peine de
     * diverger (ancrage de date, garde sur le montant, etc.).
     */
    public static function generateScheduleFor(Enrollment $enrollment, SchoolClass $schoolClass, $startDate): void
    {
        $startDate = \Carbon\Carbon::parse($startDate);

        static::create([
            'school_id' => $enrollment->school_id,
            'enrollment_id' => $enrollment->id,
            'type' => 'registration',
            'description' => 'Frais d\'inscription',
            'amount' => $schoolClass->registration_fee ?? 0,
            'paid_amount' => 0,
            'due_date' => $startDate,
            'status' => 'pending',
        ]);

        $modality = $schoolClass->payment_modality ?? 'unique';
        $count = $schoolClass->number_of_installments ?? 1;
        $installmentAmount = $schoolClass->installment_amount ?? 0;
        $ordinals = ['1ère', '2ème', '3ème', '4ème', '5ème', '6ème', '7ème', '8ème', '9ème', '10ème', '11ème', '12ème'];
        $currentDate = $startDate->copy();

        for ($i = 1; $i <= $count; $i++) {
            match ($modality) {
                'mensuel' => $currentDate->addMonth(),
                'trimestriel' => $currentDate->addMonths(3),
                'semestriel' => $currentDate->addMonths(6),
                default => $currentDate->addMonth(),
            };

            $ordinal = $ordinals[$i - 1] ?? "{$i}ème";

            static::create([
                'school_id' => $enrollment->school_id,
                'enrollment_id' => $enrollment->id,
                'type' => 'installment',
                'description' => "{$ordinal} échéance",
                'amount' => $installmentAmount,
                'paid_amount' => 0,
                'due_date' => $currentDate->copy(),
                'status' => 'pending',
            ]);
        }
    }
}