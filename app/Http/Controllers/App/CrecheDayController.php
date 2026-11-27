<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\CrecheAttendance;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\CrecheAttendanceService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Crèche > Présences du jour : par groupe, qui est attendu, présent, absent ou reparti, avec
 * les actions rapides Arrivée / Absence / Départ pour chaque enfant (écran pensé pour mobile).
 */
class CrecheDayController extends Controller
{
    public function __construct(private CrecheAttendanceService $attendances) {}

    public function index(Request $request)
    {
        $school = $this->school();
        $groupIds = $request->user()->accessibleCrecheClassIds();
        $groups = SchoolClass::whereIn('id', $groupIds)->orderBy('name')->get();

        $groupId = $request->integer('group') ?: $groups->first()?->id;
        if ($groupId && ! in_array($groupId, $groupIds, true)) {
            abort(403, 'Vous n\'êtes pas affecté(e) à ce groupe.');
        }
        $group = $groups->firstWhere('id', $groupId);

        $children = $group
            ? Student::where('school_id', $school->id)
                ->whereHas('classes', fn ($q) => $q->where('school_classes.id', $group->id))
                ->with([
                    'careProfile',
                    'authorizedPickups' => fn ($q) => $q->where('status', 'authorized')->orderBy('last_name'),
                    'crecheAttendances' => fn ($q) => $q->whereDate('date', today())->with('checkedInBy'),
                ])
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get()
            : collect();

        $todayOf = fn (Student $child): ?CrecheAttendance => $child->crecheAttendances->first();
        $counts = [
            'enrolled' => $children->count(),
            'present' => $children->filter(fn ($c) => $todayOf($c)?->status === 'present')->count(),
            'left' => $children->filter(fn ($c) => $todayOf($c)?->status === 'left')->count(),
            'absent' => $children->filter(fn ($c) => $todayOf($c)?->isAbsent())->count(),
        ];
        $counts['expected'] = $counts['enrolled'] - $counts['present'] - $counts['left'] - $counts['absent'];

        return view('app.creche.today', compact('groups', 'group', 'children', 'counts', 'todayOf'));
    }

    public function arrival(Request $request, Student $student)
    {
        $this->school();
        Gate::authorize('recordCrecheAttendance', $student);

        $data = $request->validate([
            'time' => 'nullable|date_format:H:i',
            'dropped_off_by' => 'nullable|string|max:255',
            'is_late' => 'nullable|boolean',
            'notes' => 'nullable|string|max:500',
        ]);
        $data['is_late'] = $request->boolean('is_late');

        try {
            $attendance = $this->attendances->checkIn($student, $request->user(), $data);
        } catch (DomainException $e) {
            return $this->back($student)->withErrors(['attendance' => $e->getMessage()]);
        }

        return $this->back($student)->with('success', "✅ Arrivée de {$student->first_name} enregistrée à {$attendance->checked_in_at->format('H:i')}.");
    }

    public function absence(Request $request, Student $student)
    {
        $this->school();
        Gate::authorize('recordCrecheAttendance', $student);

        $data = $request->validate([
            'justified' => 'nullable|boolean',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $this->attendances->markAbsent($student, $request->user(), $request->boolean('justified'), $data['reason'] ?? null);
        } catch (DomainException $e) {
            return $this->back($student)->withErrors(['attendance' => $e->getMessage()]);
        }

        return $this->back($student)->with('success', "Absence de {$student->first_name} enregistrée.");
    }

    /** Retour sur le groupe de l'enfant dans « Présences du jour ». */
    private function back(Student $student)
    {
        $group = $student->classes()->where('cycle', 'creche')->value('school_classes.id');

        return redirect()->route('app.creche.today', ['group' => $group]);
    }

    private function school(): School
    {
        $school = School::findOrFail(session('current_school_id'));
        abort_unless($school->isCreche(), 404);

        return $school;
    }
}
