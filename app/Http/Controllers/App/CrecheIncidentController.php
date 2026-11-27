<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CareIncident;
use App\Models\School;
use App\Models\Student;
use App\Services\CareIncidentService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Crèche > Incidents : déclaration par l'auxiliaire (enfants de ses groupes), validation ou
 * annulation par la responsable, suivis ajoutés ensuite. Un incident validé est figé.
 */
class CrecheIncidentController extends Controller
{
    public function __construct(private CareIncidentService $incidents) {}

    public function index(Request $request)
    {
        $school = $this->school();
        $groupIds = $request->user()->accessibleCrecheClassIds();
        $from = $request->filled('from') ? Carbon::parse($request->query('from')) : today()->subDays(30);
        $to = $request->filled('to') ? Carbon::parse($request->query('to')) : today();
        $status = $request->query('status');

        $base = CareIncident::where('school_id', $school->id)
            ->whereHas('student.classes', fn ($q) => $q->whereIn('school_classes.id', $groupIds));

        $incidents = (clone $base)
            ->when($status && isset(CareIncident::STATUSES[$status]), fn ($q) => $q->where('status', $status))
            // Les incidents à valider s'affichent quelle que soit la période.
            ->when($status !== 'declared', fn ($q) => $q->whereDate('occurred_on', '>=', $from)->whereDate('occurred_on', '<=', $to))
            ->with(['student', 'reporter', 'schoolClass'])
            ->orderByDesc('occurred_on')
            ->orderByDesc('occurred_at')
            ->paginate(30)
            ->withQueryString();

        return view('app.creche.incidents.index', [
            'incidents' => $incidents,
            'pendingCount' => (clone $base)->where('status', 'declared')->count(),
            'status' => $status,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function create(Request $request)
    {
        $this->school();
        $groupIds = $request->user()->accessibleCrecheClassIds();
        $children = Student::whereHas('classes', fn ($q) => $q->whereIn('school_classes.id', $groupIds))
            ->orderBy('first_name')->get()
            ->filter(fn (Student $child) => Gate::allows('recordCrecheJournal', $child))
            ->values();
        abort_if($children->isEmpty(), 403, 'Vous ne pouvez déclarer d\'incident pour aucun enfant.');

        return view('app.creche.incidents.form', [
            'incident' => new CareIncident(['occurred_on' => today(), 'occurred_at' => now()->format('H:i')]),
            'children' => $children,
            'selectedStudent' => $request->integer('student') ?: null,
        ]);
    }

    public function store(Request $request)
    {
        $this->school();
        $data = $this->validateIncident($request, withStudent: true);

        $student = Student::findOrFail($data['student_id']);
        Gate::authorize('recordCrecheJournal', $student);
        unset($data['student_id']);

        $incident = $this->incidents->declare($student, $request->user(), $data);

        return redirect()->route('app.creche.incidents.show', $incident)
            ->with('success', '⚠ Incident déclaré. Il doit maintenant être validé par la responsable.');
    }

    public function show(CareIncident $incident)
    {
        $student = $this->studentOf($incident);
        Gate::authorize('viewCrecheRecord', $student);

        $history = ActivityLog::where('subject_type', 'CareIncident')->where('subject_id', $incident->id)
            ->where('school_id', $incident->school_id)->orderBy('id')->get();

        return view('app.creche.incidents.show', [
            'incident' => $incident->load(['reporter', 'validator', 'schoolClass']),
            'student' => $student,
            'history' => $history,
            'canValidate' => Gate::allows('validateCrecheIncident', $student),
            'canEdit' => $incident->isEditable() && $this->canEdit($incident, $student),
        ]);
    }

    public function edit(CareIncident $incident)
    {
        $student = $this->studentOf($incident);
        abort_unless($incident->isEditable() && $this->canEdit($incident, $student), 403);

        return view('app.creche.incidents.form', [
            'incident' => $incident,
            'children' => collect([$student]),
            'selectedStudent' => $student->id,
        ]);
    }

    public function update(Request $request, CareIncident $incident)
    {
        $student = $this->studentOf($incident);
        abort_unless($this->canEdit($incident, $student), 403);

        $data = $this->validateIncident($request, withStudent: false);

        try {
            $this->incidents->update($incident, $request->user(), $data);
        } catch (DomainException $e) {
            return redirect()->route('app.creche.incidents.show', $incident)->withErrors(['incident' => $e->getMessage()]);
        }

        return redirect()->route('app.creche.incidents.show', $incident)->with('success', 'Incident modifié.');
    }

    private function validateIncident(Request $request, bool $withStudent): array
    {
        $data = $request->validate(array_filter([
            'student_id' => $withStudent ? 'required|integer' : null,
            'occurred_on' => 'required|date|before_or_equal:today',
            'occurred_at' => 'required|date_format:H:i',
            'type' => ['required', Rule::in(array_keys(CareIncident::TYPES))],
            'description' => 'required|string|max:3000',
            'action_taken' => 'nullable|string|max:3000',
            'person_informed' => 'nullable|string|max:150',
            'informed_at' => 'nullable|date_format:Y-m-d\TH:i',
        ]), [
            'student_id.required' => 'Choisissez l\'enfant concerné.',
            'occurred_on.before_or_equal' => 'La date de l\'incident ne peut pas être dans le futur.',
            'description.required' => 'Décrivez ce qui s\'est passé.',
            'type.required' => 'Choisissez le type d\'incident.',
        ]);

        if (Carbon::parse($data['occurred_on'].' '.$data['occurred_at'])->isFuture()) {
            throw ValidationException::withMessages(['occurred_at' => 'L\'heure de l\'incident ne peut pas être dans le futur.']);
        }

        return $data;
    }

    public function approve(Request $request, CareIncident $incident)
    {
        $student = $this->studentOf($incident);
        Gate::authorize('validateCrecheIncident', $student);
        $data = $request->validate(['comment' => 'nullable|string|max:2000']);

        return $this->act($incident, fn () => $this->incidents->validate($incident, $request->user(), $data['comment'] ?? null), '✅ Incident validé.');
    }

    public function cancel(Request $request, CareIncident $incident)
    {
        $student = $this->studentOf($incident);
        Gate::authorize('validateCrecheIncident', $student);
        $data = $request->validate(['reason' => 'required|string|max:2000'], ['reason.required' => 'Indiquez pourquoi la déclaration est annulée.']);

        return $this->act($incident, fn () => $this->incidents->cancel($incident, $request->user(), $data['reason']), 'Déclaration annulée.');
    }

    public function followUp(Request $request, CareIncident $incident)
    {
        $student = $this->studentOf($incident);
        abort_unless(Gate::allows('validateCrecheIncident', $student) || Gate::allows('recordCrecheJournal', $student), 403);
        $data = $request->validate(['text' => 'required|string|max:2000'], ['text.required' => 'Écrivez le suivi.']);

        return $this->act($incident, fn () => $this->incidents->addFollowUp($incident, $request->user(), $data['text']), 'Suivi ajouté.');
    }

    private function act(CareIncident $incident, callable $action, string $success)
    {
        try {
            $action();
        } catch (DomainException $e) {
            return redirect()->route('app.creche.incidents.show', $incident)->withErrors(['incident' => $e->getMessage()]);
        }

        return redirect()->route('app.creche.incidents.show', $incident)->with('success', $success);
    }

    /** Tant qu'il n'est pas validé : modifiable par la personne qui l'a déclaré ou la responsable. */
    private function canEdit(CareIncident $incident, Student $student): bool
    {
        return Gate::allows('validateCrecheIncident', $student)
            || ($incident->reported_by === auth()->id() && Gate::allows('recordCrecheJournal', $student));
    }

    private function studentOf(CareIncident $incident): Student
    {
        $this->school();
        abort_unless($incident->school_id === session('current_school_id'), 404);

        return Student::findOrFail($incident->student_id);
    }

    private function school(): School
    {
        $school = School::findOrFail(session('current_school_id'));
        abort_unless($school->isCreche(), 404);

        return $school;
    }
}
