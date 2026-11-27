<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CareDailySummary;
use App\Models\CareEvent;
use App\Models\CareIncident;
use App\Models\CrecheAttendance;
use App\Models\ExtraMenu;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\CareJournalService;
use App\Services\CrecheParentNotifier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Journal quotidien de la crèche — l'espace de travail de l'auxiliaire :
 *  - board  : « Que dois-je faire maintenant ? » pour son groupe (enfants présents, à faire,
 *             siestes en cours, boutons de saisie rapide) ;
 *  - create/store : saisie d'un événement pour un enfant ou pour plusieurs (mode groupe) ;
 *  - child  : la journée d'un enfant, dans l'ordre chronologique ;
 *  - edit/update/destroy/wake : correction d'un événement (suppression traçable).
 */
class CrecheJournalController extends Controller
{
    /** Types saisis par défaut pour tout le groupe présent (les autres : enfant par enfant). */
    private const GROUP_TYPES = ['meal', 'snack', 'activity'];

    public function __construct(private CareJournalService $journal) {}

    public function board(Request $request)
    {
        $school = $this->school();
        [$groups, $group] = $this->resolveGroup($request);

        $children = $group ? $this->childrenOf($group) : collect();
        $present = $children->filter(fn (Student $c) => $c->crecheAttendances->first()?->isOnSite())->values();

        $now = now()->format('H:i');
        $has = fn (Student $c, string $type) => $c->careEvents->contains('event_type', $type);
        $interval = (int) $school->setting('creche.diaper_interval_hours') * 60;

        $todo = [
            'sleeping' => $present->filter(fn ($c) => $c->careEvents->contains(fn ($e) => $e->isSleeping())),
            'meal' => $now >= $school->setting('creche.meal_time') ? $present->reject(fn ($c) => $has($c, 'meal')) : collect(),
            'snack' => $now >= $school->setting('creche.snack_time') ? $present->reject(fn ($c) => $has($c, 'snack')) : collect(),
            'diaper' => $present->filter(function (Student $c) use ($interval) {
                $last = $c->careEvents->where('event_type', 'diaper')->max('start_time')
                    ?? $c->crecheAttendances->first()?->checked_in_at?->format('H:i:s');

                return $last && Carbon::parse(today()->toDateString().' '.$last)->diffInMinutes(now()) >= $interval;
            }),
        ];

        return view('app.creche.journal.board', [
            'groups' => $groups,
            'group' => $group,
            'children' => $children,
            'present' => $present,
            'todo' => $todo,
            'types' => CareEvent::TYPES,
        ]);
    }

    public function create(Request $request)
    {
        $school = $this->school();
        $type = $request->query('type');
        abort_unless(isset(CareEvent::TYPES[$type]), 404);
        [, $group] = $this->resolveGroup($request);
        abort_unless($group, 404);

        $children = $this->childrenOf($group);
        $present = $children->filter(fn (Student $c) => $c->crecheAttendances->first()?->isOnSite())->values();
        $candidates = $present->isNotEmpty() ? $present : $children;

        $studentId = $request->integer('student') ?: null;
        $selected = $studentId
            ? [$studentId]
            : (in_array($type, self::GROUP_TYPES, true) ? $present->pluck('id')->all() : []);

        // Menu du jour de la cantine : proposé pour le repas et le goûter (pas de double saisie).
        $menu = ExtraMenu::where('school_id', $school->id)->whereDate('date', today())->first();

        return view('app.creche.journal.form', [
            'event' => new CareEvent(['event_type' => $type, 'start_time' => now()->format('H:i'), 'data' => [
                'menu' => $type === 'meal' ? $menu?->plat : ($type === 'snack' ? $menu?->gouter : null),
            ]]),
            'type' => $type,
            'group' => $group,
            'candidates' => $candidates,
            'onlyPresent' => $present->isNotEmpty(),
            'selected' => $selected,
            'school' => $school,
        ]);
    }

    public function store(Request $request)
    {
        $school = $this->school();
        $type = $request->input('event_type');
        abort_unless(isset(CareEvent::TYPES[$type]), 422);

        $input = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer',
            'group' => 'required|integer',
        ] + $this->journal->rules($type, $school), [
            'student_ids.required' => 'Sélectionnez au moins un enfant.',
        ] + CareJournalService::messages());

        if ($error = $this->futureTimeError($input)) {
            return back()->withErrors($error)->withInput();
        }

        $students = Student::whereIn('id', $input['student_ids'])->get();
        abort_if($students->count() !== count(array_unique($input['student_ids'])), 404);
        foreach ($students as $student) {
            Gate::authorize('recordCrecheJournal', $student);
        }

        $events = $this->journal->record($type, $students, $request->user(), $input);

        $label = CareEvent::TYPES[$type]['label'];
        $who = $students->count() === 1 ? $students->first()->first_name : $students->count().' enfants';

        return redirect()->route('app.creche.journal', ['group' => $input['group']])
            ->with('success', "✅ {$label} enregistré".($type === 'activity' || $type === 'observation' ? 'e' : '')." pour {$who}.")
            ->with('created_events', $events->pluck('id')->all());
    }

    /** La journée d'un enfant : arrivée, événements et départ, dans l'ordre. */
    public function child(Request $request, Student $student)
    {
        $this->school();
        Gate::authorize('viewCrecheRecord', $student);

        $date = $request->filled('date') ? Carbon::parse($request->query('date'))->startOfDay() : today();
        abort_if($date->isFuture(), 404);

        $attendance = CrecheAttendance::where('student_id', $student->id)->whereDate('date', $date)->first();
        $events = CareEvent::where('student_id', $student->id)->whereDate('event_date', $date)
            ->with('staff')->orderBy('start_time')->get();
        $incidents = CareIncident::where('student_id', $student->id)->whereDate('occurred_on', $date)
            ->where('status', '!=', 'cancelled')->get();

        $sleepMinutes = $events->where('event_type', 'sleep')->sum(fn (CareEvent $e) => $e->durationMinutes() ?? 0);

        return view('app.creche.journal.child', [
            'student' => $student->load(['classes', 'careProfile']),
            'date' => $date,
            'attendance' => $attendance,
            'timeline' => $this->timeline($attendance, $events, $incidents),
            'totals' => [
                'bottle' => $events->where('event_type', 'bottle')->count(),
                'meal' => $events->where('event_type', 'meal')->count(),
                'snack' => $events->where('event_type', 'snack')->count(),
                'sleep' => CareEvent::formatDuration($sleepMinutes ?: null),
                'diaper' => $events->where('event_type', 'diaper')->count(),
                'activity' => $events->where('event_type', 'activity')->count(),
            ],
            'canWrite' => Gate::allows('recordCrecheJournal', $student),
            'summary' => CareDailySummary::where('student_id', $student->id)->whereDate('summary_date', $date)->with('publisher')->first(),
        ]);
    }

    /**
     * Carnet du jour : l'auxiliaire publie l'humeur et un message aux parents (modifiable ensuite,
     * chaque publication est journalisée). Le jour même, ou un jour passé pour la responsable.
     */
    public function publishSummary(Request $request, Student $student, CrecheParentNotifier $notifier)
    {
        $this->school();
        Gate::authorize('recordCrecheJournal', $student);

        $data = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'mood' => ['required', Rule::in(array_keys(CareDailySummary::MOODS))],
            'message' => 'nullable|string|max:1000',
        ], [
            'mood.required' => 'Choisissez l\'humeur de la journée.',
        ]);
        $date = Carbon::parse($data['date']);
        abort_unless($date->isToday() || $request->user()->hasPermission('creche.manage'), 403,
            'Seule la responsable peut publier le carnet d\'un jour passé.');

        $summary = CareDailySummary::where('student_id', $student->id)->whereDate('summary_date', $date)->first()
            ?? new CareDailySummary(['summary_date' => $date->toDateString()]);
        $isFirstPublication = ! $summary->exists;

        $summary->fill(['mood' => $data['mood'], 'message' => $data['message'] ?? null]);
        $summary->school_id = $student->school_id;
        $summary->student_id = $student->id;
        $summary->published_by = $request->user()->id;
        $summary->published_at = now();
        $summary->save();

        ActivityLog::audit(
            $isFirstPublication ? 'creche.summary.published' : 'creche.summary.updated',
            "Carnet du {$date->format('d/m/Y')} de {$student->first_name} {$student->last_name} ".($isFirstPublication ? 'publié' : 'modifié')." ({$summary->mood_label})",
            $student,
            ['summary_id' => $summary->id, 'date' => $date->toDateString(), 'mood' => $summary->mood, 'message' => $summary->message]
        );

        if ($isFirstPublication) {
            $notifier->summary($student);
        }

        return redirect()->route('app.creche.journal.child', ['student' => $student, 'date' => $date->toDateString()])
            ->with('success', "📒 Carnet du jour de {$student->first_name} ".($isFirstPublication ? 'publié aux parents.' : 'mis à jour.'));
    }

    public function edit(CareEvent $event)
    {
        $school = $this->school();
        $student = $this->authorizeEdit($event);

        return view('app.creche.journal.form', [
            'event' => $event,
            'type' => $event->event_type,
            'group' => $event->schoolClass,
            'candidates' => collect([$student]),
            'onlyPresent' => false,
            'selected' => [$student->id],
            'school' => $school,
        ]);
    }

    public function update(Request $request, CareEvent $event)
    {
        $school = $this->school();
        $student = $this->authorizeEdit($event);

        $input = $request->validate($this->journal->rules($event->event_type, $school), CareJournalService::messages());
        if ($error = $this->futureTimeError($input, $event->event_date)) {
            return back()->withErrors($error)->withInput();
        }

        $this->journal->update($event, $request->user(), $input);

        return redirect()->route('app.creche.journal.child', ['student' => $student, 'date' => $event->event_date->toDateString()])
            ->with('success', 'Événement modifié.');
    }

    public function destroy(Request $request, CareEvent $event)
    {
        $this->school();
        $student = $this->authorizeEdit($event);
        $date = $event->event_date->toDateString();

        $this->journal->delete($event, $request->user());

        return redirect()->route('app.creche.journal.child', ['student' => $student, 'date' => $date])
            ->with('success', 'Événement supprimé (il reste visible dans le journal d\'audit).');
    }

    /** Réveil d'un enfant qui dort : fin de la sieste à l'heure actuelle (ou saisie). */
    public function wake(Request $request, CareEvent $event)
    {
        $this->school();
        $student = $this->authorizeEdit($event);
        abort_unless($event->isSleeping(), 422);

        $data = $request->validate(['end_time' => 'nullable|date_format:H:i']);
        $time = $data['end_time'] ?? now()->format('H:i');
        if ($time <= $event->startLabel()) {
            return back()->withErrors(['end_time' => 'L\'heure de réveil doit être après l\'heure de coucher.']);
        }

        $this->journal->wake($event, $request->user(), $time);

        return back()->with('success', "{$student->first_name} est réveillé(e) ({$event->summary()}).");
    }

    /**
     * Un événement se corrige par qui peut saisir le journal de l'enfant : le jour même pour
     * une auxiliaire, à tout moment pour la responsable.
     */
    private function authorizeEdit(CareEvent $event): Student
    {
        abort_unless($event->school_id === session('current_school_id'), 404);
        $student = Student::findOrFail($event->student_id);
        Gate::authorize('recordCrecheJournal', $student);

        abort_unless($event->event_date->isToday() || auth()->user()->hasPermission('creche.manage'), 403,
            'Seule la responsable peut corriger le journal d\'un jour passé.');

        return $student;
    }

    /** Pas d'heure dans le futur pour un événement du jour. */
    private function futureTimeError(array $input, ?Carbon $date = null): ?array
    {
        if ($date && ! $date->isToday()) {
            return null;
        }

        $now = now()->format('H:i');
        foreach (['start_time' => 'L\'heure', 'end_time' => 'L\'heure de fin'] as $field => $label) {
            if (! empty($input[$field]) && $input[$field] > $now) {
                return [$field => "{$label} ne peut pas être dans le futur."];
            }
        }

        return null;
    }

    /** Arrivée, événements, incidents et départ fusionnés, dans l'ordre chronologique. */
    private function timeline(?CrecheAttendance $attendance, Collection $events, Collection $incidents): Collection
    {
        $items = $events->map(fn (CareEvent $e) => ['time' => $e->startLabel(), 'kind' => 'event', 'event' => $e])
            ->concat($incidents->map(fn (CareIncident $i) => ['time' => $i->timeLabel(), 'kind' => 'incident', 'incident' => $i]));

        if ($attendance?->checked_in_at) {
            $items->push(['time' => $attendance->checked_in_at->format('H:i'), 'kind' => 'arrival', 'attendance' => $attendance]);
        }
        if ($attendance?->checked_out_at) {
            $items->push(['time' => $attendance->checked_out_at->format('H:i'), 'kind' => 'departure', 'attendance' => $attendance]);
        }

        // À heure égale : arrivée d'abord, départ en dernier.
        $order = ['arrival' => 0, 'event' => 1, 'incident' => 1, 'departure' => 2];

        return $items->sortBy([['time', 'asc'], fn ($a, $b) => $order[$a['kind']] <=> $order[$b['kind']]])->values();
    }

    /** Groupes accessibles et groupe choisi (?group=, sinon le premier). */
    private function resolveGroup(Request $request): array
    {
        $groupIds = $request->user()->accessibleCrecheClassIds();
        $groups = SchoolClass::whereIn('id', $groupIds)->orderBy('name')->get();

        $groupId = $request->integer('group') ?: $groups->first()?->id;
        if ($groupId && ! in_array($groupId, $groupIds, true)) {
            abort(403, 'Vous n\'êtes pas affecté(e) à ce groupe.');
        }

        return [$groups, $groups->firstWhere('id', $groupId)];
    }

    /** Enfants du groupe avec leur présence et leurs événements du jour. */
    private function childrenOf(SchoolClass $group): Collection
    {
        return Student::where('school_id', $group->school_id)
            ->whereHas('classes', fn ($q) => $q->where('school_classes.id', $group->id))
            ->with([
                'careProfile',
                'crecheAttendances' => fn ($q) => $q->whereDate('date', today()),
                'careEvents' => fn ($q) => $q->whereDate('event_date', today())->orderBy('start_time'),
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    private function school(): School
    {
        $school = School::findOrFail(session('current_school_id'));
        abort_unless($school->isCreche(), 404);

        return $school;
    }
}
