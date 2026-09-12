<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\CrecheAttendance;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;

class CrecheAttendanceController extends Controller
{
    /**
     * Grille de pointage (arrivée/départ) pour un groupe crèche à une date donnée.
     */
    public function index(Request $request)
    {
        $schoolId = session('current_school_id');
        $classId = $request->get('class_id');
        $date = $request->get('date', now()->format('Y-m-d'));

        $classes = SchoolClass::where('school_id', $schoolId)
            ->where('cycle', 'creche')
            ->orderBy('name')
            ->get();

        $students = collect();
        if ($classId) {
            $students = Student::where('school_id', $schoolId)
                ->whereHas('classes', function ($q) use ($classId) {
                    $q->where('school_classes.id', $classId);
                })
                ->with(['crecheAttendances' => fn ($q) => $q->where('date', $date)])
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }

        return view('app.creche.attendances.index', compact('classes', 'classId', 'date', 'students'));
    }

    public function store(Request $request)
    {
        $schoolId = session('current_school_id');

        $validated = $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'date' => 'required|date',
            'records' => 'required|array',
            'records.*.status' => 'required|in:present,absent',
            'records.*.checked_in_at' => 'nullable|date_format:H:i',
            'records.*.checked_out_at' => 'nullable|date_format:H:i',
            'records.*.dropped_off_by' => 'nullable|string|max:255',
            'records.*.picked_up_by' => 'nullable|string|max:255',
        ]);

        foreach ($validated['records'] as $studentId => $data) {
            $student = Student::where('school_id', $schoolId)->find($studentId);
            if (! $student) {
                continue;
            }

            CrecheAttendance::updateOrCreate(
                ['student_id' => $studentId, 'date' => $validated['date']],
                [
                    'school_id' => $schoolId,
                    'school_class_id' => $validated['class_id'],
                    'status' => $data['status'],
                    'checked_in_at' => ! empty($data['checked_in_at']) ? $validated['date'].' '.$data['checked_in_at'] : null,
                    'checked_out_at' => ! empty($data['checked_out_at']) ? $validated['date'].' '.$data['checked_out_at'] : null,
                    'dropped_off_by' => $data['dropped_off_by'] ?? null,
                    'picked_up_by' => $data['picked_up_by'] ?? null,
                    'marked_by' => auth()->id(),
                ]
            );
        }

        return redirect()->route('app.creche-attendances.index', ['class_id' => $validated['class_id'], 'date' => $validated['date']])
            ->with('success', '✅ Pointage enregistré avec succès !');
    }
}
