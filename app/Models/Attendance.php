<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'school_class_id',
        'date',
        'period',
        'status',
        'marked_by',
        'notes',
        'notified_parent',
    ];

    protected $casts = [
        'date' => 'date',
        'notified_parent' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    // Helpers
    public function isPresent(): bool { return $this->status === 'present'; }
    public function isAbsent(): bool { return $this->status === 'absent'; }
    public function isLate(): bool { return $this->status === 'late'; }
    public function isExcused(): bool { return $this->status === 'excused'; }
}