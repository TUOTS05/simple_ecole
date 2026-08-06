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
}