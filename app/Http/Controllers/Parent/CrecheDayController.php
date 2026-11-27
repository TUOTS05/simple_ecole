<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Services\CareDayReport;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Espace parent > « La journée de mon enfant » : le carnet de vie d'une journée à la crèche.
 * Un parent ne voit que ses propres enfants (même en changeant l'identifiant dans l'URL).
 */
class CrecheDayController extends Controller
{
    public function show(Request $request, $studentId, CareDayReport $report)
    {
        $student = $request->user()->children()->where('students.id', $studentId)->with(['school', 'classes'])->firstOrFail();
        abort_unless($student->crecheClass() || $student->careEvents()->exists(), 404);

        $date = $request->filled('date') ? Carbon::parse($request->query('date'))->startOfDay() : today();
        abort_if($date->isFuture(), 404);

        return view('parent.creche.day', [
            'student' => $student,
            'date' => $date,
            'day' => $report->build($student, $date),
        ]);
    }
}
