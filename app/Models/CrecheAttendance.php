<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrecheAttendance extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'school_class_id',
        'date',
        'status',
        'checked_in_at',
        'checked_out_at',
        'dropped_off_by',
        'picked_up_by',
        'notes',
        'marked_by',
    ];

    protected $casts = [
        'date' => 'date',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
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
}
