<?php

namespace App\Events;

use App\Models\Attendance;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement déclenché quand un élève est marqué absent lors de l'appel.
 */
class StudentMarkedAbsent
{
    use Dispatchable, SerializesModels;

    public Attendance $attendance;

    public function __construct(Attendance $attendance)
    {
        $this->attendance = $attendance;
    }
}
