<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtraStockMovement extends Model
{
    protected $fillable = [
        'school_id', 'extra_stock_item_id', 'type', 'quantity', 'unit_price',
        'student_id', 'reason', 'processed_by', 'processed_at', 'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    /**
     * Sens du mouvement sur le stock : entrées/retours réapprovisionnent,
     * sorties/ventes consomment.
     */
    public const INBOUND_TYPES = ['in', 'return'];

    public const OUTBOUND_TYPES = ['out', 'sale'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ExtraStockItem::class, 'extra_stock_item_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
