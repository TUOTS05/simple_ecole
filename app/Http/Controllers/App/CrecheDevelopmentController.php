<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DevelopmentObservation;
use App\Models\School;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Suivi du développement d'un enfant : dernier niveau observé par domaine et historique.
 * Ce ne sont pas des notes : observé / en cours / à stimuler.
 */
class CrecheDevelopmentController extends Controller
{
    public function index(Student $student)
    {
        $school = $this->school();
        Gate::authorize('viewCrecheRecord', $student);

        $observations = DevelopmentObservation::where('student_id', $student->id)
            ->with('staff')->orderByDesc('observed_on')->orderByDesc('id')->get();

        $domains = (array) $school->setting('creche.development_domains');
        // Dernier niveau par domaine (y compris un domaine retiré des paramètres mais déjà observé).
        $latest = $observations->unique('domain')->keyBy('domain');

        return view('app.creche.development', [
            'student' => $student,
            'domains' => array_values(array_unique([...$domains, ...$latest->keys()->all()])),
            'activeDomains' => $domains,
            'latest' => $latest,
            'observations' => $observations,
            'canWrite' => Gate::allows('recordCrecheJournal', $student),
        ]);
    }

    public function store(Request $request, Student $student)
    {
        $school = $this->school();
        Gate::authorize('recordCrecheJournal', $student);

        $data = $request->validate([
            'observed_on' => 'required|date|before_or_equal:today',
            'domain' => ['required', Rule::in((array) $school->setting('creche.development_domains'))],
            'level' => ['required', Rule::in(array_keys(DevelopmentObservation::LEVELS))],
            'notes' => 'nullable|string|max:2000',
        ], [
            'domain.required' => 'Choisissez le domaine.',
            'level.required' => 'Choisissez le niveau.',
            'observed_on.before_or_equal' => 'La date ne peut pas être dans le futur.',
        ]);

        $observation = new DevelopmentObservation($data);
        $observation->school_id = $student->school_id;
        $observation->student_id = $student->id;
        $observation->staff_id = $request->user()->id;
        $observation->save();

        ActivityLog::audit(
            'creche.development.added',
            "Développement de {$student->first_name} {$student->last_name} — {$observation->domain} : {$observation->level_label}",
            $student,
            ['observation_id' => $observation->id, 'domain' => $observation->domain, 'level' => $observation->level]
        );

        return redirect()->route('app.creche.development', $student)->with('success', 'Observation enregistrée.');
    }

    /** Retrait (suppression douce, tracée) : par son auteur le jour même, ou par la responsable. */
    public function destroy(Request $request, DevelopmentObservation $observation)
    {
        $this->school();
        abort_unless($observation->school_id === session('current_school_id'), 404);
        $student = Student::findOrFail($observation->student_id);
        Gate::authorize('recordCrecheJournal', $student);

        $isAuthorSameDay = $observation->staff_id === $request->user()->id && $observation->created_at->isToday();
        abort_unless($isAuthorSameDay || $request->user()->hasPermission('creche.manage'), 403);

        $observation->deleted_by = $request->user()->id;
        $observation->save();
        $observation->delete();

        ActivityLog::audit(
            'creche.development.deleted',
            "Observation de développement retirée pour {$student->first_name} {$student->last_name} ({$observation->domain})",
            $student,
            ['observation_id' => $observation->id, 'domain' => $observation->domain, 'level' => $observation->level, 'notes' => $observation->notes]
        );

        return redirect()->route('app.creche.development', $student)->with('success', 'Observation retirée.');
    }

    private function school(): School
    {
        $school = School::findOrFail(session('current_school_id'));
        abort_unless($school->isCreche(), 404);

        return $school;
    }
}
