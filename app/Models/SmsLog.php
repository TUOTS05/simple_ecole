<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $fillable = [
        'school_id', 'student_id', 'recipient_phone', 'recipient_name',
        'message', 'gateway', 'status', 'error_message', 'external_id',
        'cost', 'trigger_type', 'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'cost' => 'decimal:2',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
