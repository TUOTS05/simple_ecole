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
    

    protected $casts = [
        'sent_at' => 'datetime',
    ];

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
}