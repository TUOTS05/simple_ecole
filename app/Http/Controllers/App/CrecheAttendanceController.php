<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CrecheAttendance;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;

class CrecheAttendanceController extends Controller
{
    /**
     * Grille de pointage (arrivée/départ) pour un groupe crèche à une date donnée.
     * Seuls les groupes accessibles à l'utilisateur sont proposés (un auxiliaire : ses groupes).
     */
    public function index(Request $request)
    {
        $schoolId = session('current_school_id');
        $accessibleIds = $request->user()->accessibleCrecheClassIds();
        $classId = $request->get('class_id');
        $date = $request->get('date', now()->format('Y-m-d'));

        if ($classId && ! in_array((int) $classId, $accessibleIds, true)) {
            abort(403, 'Vous n\'êtes pas affecté(e) à ce groupe.');
        }

        $classes = SchoolClass::where('school_id', $schoolId)
            ->whereIn('id', $accessibleIds)
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
            'class_id' => 'required|integer',
            'date' => 'required|date',
            'records' => 'required|array',
            'records.*.status' => 'required|in:present,absent',
            'records.*.checked_in_at' => 'nullable|date_format:H:i',
            'records.*.checked_out_at' => 'nullable|date_format:H:i',
            'records.*.dropped_off_by' => 'nullable|string|max:255',
            'records.*.picked_up_by' => 'nullable|string|max:255',
        ]);

        $classId = (int) $validated['class_id'];
        if (! $request->user()->canAccessCrecheClass($classId)) {
            abort(403, 'Vous n\'êtes pas affecté(e) à ce groupe.');
        }

        // Seuls les enfants du groupe peuvent être pointés (pas un identifiant modifié à la main).
        $studentIds = Student::where('school_id', $schoolId)
            ->whereHas('classes', fn ($q) => $q->where('school_classes.id', $classId))
            ->pluck('id')
            ->all();

        $saved = 0;
        foreach ($validated['records'] as $studentId => $data) {
            if (! in_array((int) $studentId, $studentIds, true)) {
                continue;
            }

            CrecheAttendance::updateOrCreate(
                ['student_id' => $studentId, 'date' => $validated['date']],
                [
                    'school_id' => $schoolId,
                    'school_class_id' => $classId,
                    'status' => $data['status'],
                    'checked_in_at' => ! empty($data['checked_in_at']) ? $validated['date'].' '.$data['checked_in_at'] : null,
                    'checked_out_at' => ! empty($data['checked_out_at']) ? $validated['date'].' '.$data['checked_out_at'] : null,
                    'dropped_off_by' => $data['dropped_off_by'] ?? null,
                    'picked_up_by' => $data['picked_up_by'] ?? null,
                    'marked_by' => auth()->id(),
                ]
            );
            $saved++;
        }

        $class = SchoolClass::find($classId);
        ActivityLog::audit(
            'creche.attendance.saved',
            "A enregistré le pointage du groupe {$class?->name} du {$validated['date']} ({$saved} enfant(s))",
            $class,
            ['date' => $validated['date'], 'children' => $saved]
        );

        return redirect()->route('app.creche-attendances.index', ['class_id' => $classId, 'date' => $validated['date']])
            ->with('success', '✅ Pointage enregistré avec succès !');
    }
}
