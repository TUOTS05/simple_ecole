<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionRequest extends Model
{
    protected $fillable = ['school_id', 'plan_id', 'duration', 'status', 'admin_notes'];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }
}
