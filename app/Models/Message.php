<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'school_id',
        'sender_id',
        'subject',
        'message',
        'reply',
        'is_read',
        'replied_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'replied_at' => 'datetime',
    ];

    // Relation avec l'école
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    // Relation avec le parent (expéditeur)
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}