<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $table = 'notifications_log';

    protected $fillable = [
        'school_id',
        'student_id',
        'installment_id',
        'extra_installment_id',
        'parent_id',
        'type',
        'category',
        'recipient_phone',
        'recipient_email',
        'message',
        'status',
        'error_message',
        'provider_response_id',
    ];

    protected $casts = [];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(StudentInstallment::class, 'installment_id');
    }

    public function extraInstallment(): BelongsTo
    {
        return $this->belongsTo(ExtraInstallment::class, 'extra_installment_id');
    }

    /**
     * Vérifie si une notification a déjà été envoyée pour cette échéance
     */
    public static function alreadySentForInstallment(int $installmentId, string $type = 'sms'): bool
    {
        return self::where('installment_id', $installmentId)
            ->where('type', $type)
            ->where('category', 'late_payment')
            ->where('status', 'sent')
            ->exists();
    }

    /**
     * Vérifie si une notification d'une catégorie donnée a déjà été envoyée
     * pour cette échéance d'extra (évite les doublons de rappel automatique).
     */
    public static function alreadySentForExtraInstallment(int $extraInstallmentId, string $category, string $type = 'email'): bool
    {
        return self::where('extra_installment_id', $extraInstallmentId)
            ->where('type', $type)
            ->where('category', $category)
            ->where('status', 'sent')
            ->exists();
    }
}
