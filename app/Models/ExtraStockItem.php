<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExtraStockItem extends Model
{
    protected $fillable = [
        'school_id', 'name', 'description', 'unit',
        'unit_price', 'quantity_on_hand', 'alert_threshold', 'status',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity_on_hand' => 'integer',
        'alert_threshold' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ExtraStockMovement::class);
    }

    public function isLowStock(): bool
    {
        return $this->alert_threshold !== null && $this->quantity_on_hand <= $this->alert_threshold;
    }
}
