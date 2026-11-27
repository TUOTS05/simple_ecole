<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CrecheAttendance;
use App\Models\SchoolClass;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Historique du pointage crèche par groupe et par date. Le pointage du jour se fait enfant par
 * enfant (Présences du jour, Départ enfant) ; cette grille sert à consulter et, pour la
 * responsable, à corriger un oubli. Une correction est journalisée avec l'état avant/après et
 * un départ corrigé est marqué « correction » (la personne n'a pas été vérifiée).
 */
class CrecheAttendanceController extends Controller
{
    /** Seuls les groupes accessibles à l'utilisateur sont proposés (un auxiliaire : ses groupes). */
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
                ->with(['crecheAttendances' => fn ($q) => $q->whereDate('date', $date)])
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }

        $canCorrect = $request->user()->hasPermission('creche.manage');

        return view('app.creche.attendances.index', compact('classes', 'classId', 'date', 'students', 'canCorrect'));
    }

    public function store(Request $request)
    {
        $schoolId = session('current_school_id');

        $validated = $request->validate([
            'class_id' => 'required|integer',
            'date' => 'required|date',
            'records' => 'required|array',
            'records.*.status' => ['required', Rule::in(array_keys(CrecheAttendance::STATUSES))],
            'records.*.checked_in_at' => 'nullable|date_format:H:i',
            'records.*.checked_out_at' => 'nullable|date_format:H:i',
            'records.*.dropped_off_by' => 'nullable|string|max:255',
            'records.*.picked_up_by' => 'nullable|string|max:255',
        ]);

        // Pas de pointage par anticipation : les journées futures se pointent le jour même.
        if (Carbon::parse($validated['date'])->isFuture()) {
            return back()->withErrors(['date' => 'On ne peut pas pointer une journée à venir.']);
        }

        $classId = (int) $validated['class_id'];
        if (! $request->user()->canAccessCrecheClass($classId)) {
            abort(403, 'Vous n\'êtes pas affecté(e) à ce groupe.');
        }

        // Seuls les enfants du groupe peuvent être pointés (pas un identifiant modifié à la main).
        $studentIds = Student::where('school_id', $schoolId)
            ->whereHas('classes', fn ($q) => $q->where('school_classes.id', $classId))
            ->pluck('id')
            ->all();

        $class = SchoolClass::with('school')->find($classId);
        $closing = $class->effectiveClosingTime();
        $tracked = ['status', 'checked_in_at', 'checked_out_at', 'dropped_off_by', 'picked_up_by'];
        $changes = [];

        foreach ($validated['records'] as $studentId => $data) {
            if (! in_array((int) $studentId, $studentIds, true)) {
                continue;
            }

            $attendance = CrecheAttendance::where('student_id', $studentId)->whereDate('date', $validated['date'])->first()
                ?? new CrecheAttendance(['student_id' => (int) $studentId, 'date' => $validated['date']]);

            $isAbsent = in_array($data['status'], ['absent', 'absent_justified'], true);
            $checkedOut = ! $isAbsent && ! empty($data['checked_out_at']) ? $validated['date'].' '.$data['checked_out_at'] : null;
            // Une heure de départ saisie vaut départ ; « parti » sans heure reste « présent ».
            $status = $isAbsent ? $data['status'] : ($checkedOut ? 'left' : 'present');

            $attendance->fill([
                'school_id' => $schoolId,
                'school_class_id' => $classId,
                'status' => $status,
                'checked_in_at' => ! $isAbsent && ! empty($data['checked_in_at']) ? $validated['date'].' '.$data['checked_in_at'] : null,
                'checked_out_at' => $checkedOut,
                'dropped_off_by' => $isAbsent ? null : ($data['dropped_off_by'] ?? null),
                'picked_up_by' => $checkedOut ? ($data['picked_up_by'] ?? null) : null,
            ]);

            $dirty = array_intersect($tracked, array_keys($attendance->getDirty()));
            if (! $attendance->exists && $status === 'present' && ! $attendance->checked_in_at) {
                continue; // ligne vide : enfant non pointé, on ne crée rien
            }
            if ($attendance->exists && ! $dirty) {
                continue;
            }

            $before = $attendance->exists ? $this->snapshot(new CrecheAttendance($attendance->getOriginal())) : null;

            // Départ corrigé à la main : la personne n'a pas été vérifiée dans la liste.
            if (in_array('checked_out_at', $dirty, true) || in_array('picked_up_by', $dirty, true)) {
                $attendance->pickup_person_id = null;
                $attendance->verification_mode = $checkedOut ? 'correction' : null;
                $attendance->checked_out_by = $checkedOut ? $request->user()->id : null;
            }
            $attendance->late_pickup = $checkedOut && $closing !== '' && substr($data['checked_out_at'], 0, 5) > $closing;
            $attendance->marked_by = $request->user()->id;
            $attendance->save();

            $changes[] = ['student_id' => (int) $studentId, 'before' => $before, 'after' => $this->snapshot($attendance)];
        }

        if ($changes) {
            ActivityLog::audit(
                'creche.attendance.corrected',
                "A corrigé le pointage du groupe {$class->name} du {$validated['date']} (".count($changes).' enfant(s))',
                $class,
                ['date' => $validated['date'], 'children' => count($changes), 'changes' => $changes]
            );
        }

        return redirect()->route('app.creche-attendances.index', ['class_id' => $classId, 'date' => $validated['date']])
            ->with('success', $changes ? '✅ Pointage corrigé ('.count($changes).' enfant(s)).' : 'Aucune modification.');
    }

    /** État d'une présence pour le journal d'audit. */
    private function snapshot(CrecheAttendance $attendance): array
    {
        return [
            'status' => $attendance->status,
            'in' => $attendance->checked_in_at?->format('H:i'),
            'out' => $attendance->checked_out_at?->format('H:i'),
            'dropped_off_by' => $attendance->dropped_off_by,
            'picked_up_by' => $attendance->picked_up_by,
        ];
    }
}
