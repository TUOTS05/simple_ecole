<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CareDayReport;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * API parent (application mobile, jetons Sanctum) : carnet de vie d'une journée à la crèche.
 * Lecture seule ; un parent ne lit que ses propres enfants (404 sinon).
 */
class ParentCrecheController extends Controller
{
    public function day(Request $request, $studentId, CareDayReport $report)
    {
        $request->validate(['date' => 'nullable|date|before_or_equal:today']);

        $student = $request->user()->children()->where('students.id', $studentId)->with('school')->firstOrFail();
        $date = $request->filled('date') ? Carbon::parse($request->query('date'))->startOfDay() : today();

        return response()->json([
            'data' => [
                'child' => ['id' => $student->id, 'first_name' => $student->first_name],
            ] + $report->build($student, $date),
        ]);
    }
}
