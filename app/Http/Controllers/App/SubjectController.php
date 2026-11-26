<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Support\SchoolSettings;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    private const LEVELS_BY_CYCLE = [
        'maternelle' => ['TPS', 'PS', 'MS', 'GS'],
        'primaire' => ['CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2'],
    ];

    public function index(Request $request)
    {
        $schoolId = session('current_school_id');
        $currentYear = SchoolYear::where('school_id', $schoolId)->where('is_active', true)->first();

        $query = Subject::where('school_id', $schoolId)
            ->where('school_year_id', $currentYear?->id);

        if ($request->filled('cycle')) {
            $query->where('cycle', $request->cycle);
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        $subjects = $query->orderBy('cycle')->orderBy('level')->ordered()->get();

        // Sources possibles pour « Copier les matières » : chaque niveau de chaque année déjà configuré
        $copySources = Subject::where('subjects.school_id', $schoolId)
            ->join('school_years', 'school_years.id', '=', 'subjects.school_year_id')
            ->select('subjects.school_year_id', 'subjects.level', 'subjects.cycle', 'school_years.name as year_name', DB::raw('count(*) as total'))
            ->groupBy('subjects.school_year_id', 'subjects.level', 'subjects.cycle', 'school_years.name')
            ->orderByDesc('subjects.school_year_id')
            ->orderBy('subjects.level')
            ->get();

        return view('app.subjects.index', [
            'subjects' => $subjects,
            'currentYear' => $currentYear,
            'copySources' => $copySources,
            'levelsByCycle' => $this->levelsForSchool(),
            'domains' => $this->knownDomains($schoolId),
        ]);
    }

    public function create()
    {
        $school = session('current_school');
        $schoolModel = School::find(session('current_school_id'));
        $currentYear = SchoolYear::where('school_id', session('current_school_id'))->where('is_active', true)->first();

        $levelsByCycle = self::LEVELS_BY_CYCLE;

        // Liste de matières proposée : celle définie par l'école (Paramètres › Personnalisation)
        $defaultSubjects = [
            'maternelle' => array_values((array) SchoolSettings::get($schoolModel, 'subjects.maternelle')),
            'primaire' => array_values((array) SchoolSettings::get($schoolModel, 'subjects.primaire')),
        ];
        $domains = $this->knownDomains(session('current_school_id'));

        return view('app.subjects.create', compact('school', 'currentYear', 'levelsByCycle', 'defaultSubjects', 'domains'));
    }

    public function store(Request $request)
    {
        // On ignore les lignes laissées vides (sans nom) et on complète les valeurs
        // par défaut, puis on valide CES données filtrées (et non la requête brute,
        // sinon une ligne vide fait échouer tout l'enregistrement).
        $subjects = collect($request->input('subjects', []))
            ->filter(fn ($subject) => is_array($subject) && trim((string) ($subject['name'] ?? '')) !== '')
            ->map(fn ($subject) => [
                'name' => trim($subject['name']),
                'domain' => trim((string) ($subject['domain'] ?? '')) ?: null,
                'coefficient' => ($subject['coefficient'] ?? '') !== '' ? $subject['coefficient'] : 1,
                'max_score' => ($subject['max_score'] ?? '') !== '' ? $subject['max_score'] : 20,
            ])
            ->values()
            ->all();

        if (empty($subjects)) {
            return back()->withErrors(['subjects' => 'Veuillez ajouter au moins une matière avec un nom.'])
                ->withInput();
        }

        $request->merge(['subjects' => $subjects]);

        $validated = $request->validate([
            'cycle' => 'required|in:maternelle,primaire',
            'level' => 'required|string|max:10',
            'subjects' => 'required|array|min:1',
            'subjects.*.name' => 'required|string|max:255',
            'subjects.*.domain' => 'nullable|string|max:100',
            'subjects.*.coefficient' => 'required|numeric|min:1|max:10',
            'subjects.*.max_score' => 'required|numeric|in:10,20,40,50,100',
        ], [
            'cycle.required' => 'Veuillez sélectionner un cycle.',
            'cycle.in' => 'Le cycle sélectionné est invalide.',
            'level.required' => 'Veuillez sélectionner un niveau.',
            'subjects.*.name.max' => 'Le nom de la matière n° :position est trop long (255 caractères max).',
            'subjects.*.domain.max' => 'Le domaine de la matière n° :position est trop long (100 caractères max).',
            'subjects.*.coefficient.numeric' => 'Le coefficient de la matière n° :position doit être un nombre.',
            'subjects.*.coefficient.min' => 'Le coefficient de la matière n° :position doit être au moins 1.',
            'subjects.*.coefficient.max' => 'Le coefficient de la matière n° :position ne peut pas dépasser 10.',
            'subjects.*.max_score.in' => 'Le barème de la matière n° :position doit être 10, 20, 40, 50 ou 100.',
        ]);

        $schoolId = session('current_school_id');
        $currentYear = SchoolYear::where('school_id', $schoolId)->where('is_active', true)->first();

        if (! $currentYear) {
            return back()->withErrors(['error' => 'Aucune année scolaire active trouvée.'])
                ->withInput();
        }

        $scope = [
            'school_id' => $schoolId,
            'school_year_id' => $currentYear->id,
            'cycle' => $validated['cycle'],
            'level' => $validated['level'],
        ];
        // Les nouvelles matières se placent après celles déjà configurées, dans l'ordre du formulaire.
        $nextOrder = (int) Subject::where($scope)->max('sort_order');

        $count = 0;
        foreach ($validated['subjects'] as $subjectData) {
            $subject = Subject::firstOrNew($scope + ['name' => $subjectData['name']]);
            $subject->fill([
                'domain' => $subjectData['domain'] ?? $subject->domain,
                'coefficient' => $subjectData['coefficient'],
                'max_score' => $subjectData['max_score'],
                'is_active' => true,
            ]);
            if (! $subject->exists) {
                $subject->sort_order = ++$nextOrder;
            }
            $subject->save();
            $count++;
        }

        return redirect()->route('app.subjects.index')
            ->with('success', "{$count} matière(s) enregistrée(s) avec succès !");
    }

    /**
     * Enregistre en une fois l'ordre, le domaine, le coefficient et le barème
     * des matières affichées dans la liste.
     */
    public function bulkUpdate(Request $request)
    {
        $schoolId = session('current_school_id');

        $validated = $request->validate([
            'subjects' => 'required|array',
            'subjects.*.sort_order' => 'nullable|integer|min:0|max:9999',
            'subjects.*.domain' => 'nullable|string|max:100',
            'subjects.*.coefficient' => 'required|numeric|min:1|max:10',
            'subjects.*.max_score' => 'required|numeric|in:10,20,40,50,100',
        ], [
            'subjects.*.coefficient.min' => 'Un coefficient doit être au moins 1.',
            'subjects.*.coefficient.max' => 'Un coefficient ne peut pas dépasser 10.',
            'subjects.*.max_score.in' => 'Le barème doit être 10, 20, 40, 50 ou 100.',
        ]);

        $subjects = Subject::where('school_id', $schoolId)
            ->whereIn('id', array_keys($validated['subjects']))
            ->get();

        foreach ($subjects as $subject) {
            $data = $validated['subjects'][$subject->id];
            $subject->update([
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'domain' => trim((string) ($data['domain'] ?? '')) ?: null,
                'coefficient' => $data['coefficient'],
                'max_score' => $data['max_score'],
            ]);
        }

        return back()->with('success', "{$subjects->count()} matière(s) mise(s) à jour.");
    }

    /**
     * Copie les matières d'un niveau (de n'importe quelle année) vers un niveau de l'année
     * en cours. Les matières déjà présentes sur le niveau cible (même nom) sont conservées.
     */
    public function copy(Request $request)
    {
        $schoolId = session('current_school_id');
        $currentYear = SchoolYear::where('school_id', $schoolId)->where('is_active', true)->first();

        if (! $currentYear) {
            return back()->withErrors(['copy' => 'Aucune année scolaire active trouvée.']);
        }

        $allLevels = array_merge(...array_values(self::LEVELS_BY_CYCLE));
        $validated = $request->validate([
            'source' => ['required', 'regex:/^\d+\|[A-Z0-9]+$/'],
            'target_levels' => 'required|array|min:1',
            'target_levels.*' => ['string', Rule::in($allLevels)],
        ], [
            'source.required' => 'Choisissez les matières à copier.',
            'target_levels.required' => 'Choisissez au moins un niveau de destination.',
        ]);

        [$sourceYearId, $sourceLevel] = explode('|', $validated['source']);
        $sourceSubjects = Subject::where('school_id', $schoolId)
            ->where('school_year_id', (int) $sourceYearId)
            ->where('level', $sourceLevel)
            ->ordered()
            ->get();

        if ($sourceSubjects->isEmpty()) {
            return back()->withErrors(['copy' => 'Aucune matière à copier pour ce niveau.']);
        }

        $created = 0;
        foreach ($validated['target_levels'] as $targetLevel) {
            $cycle = collect(self::LEVELS_BY_CYCLE)->search(fn ($levels) => in_array($targetLevel, $levels, true));

            foreach ($sourceSubjects as $source) {
                if ((int) $sourceYearId === $currentYear->id && $targetLevel === $sourceLevel) {
                    continue; // copie sur lui-même
                }

                $attributes = [
                    'school_id' => $schoolId,
                    'school_year_id' => $currentYear->id,
                    'cycle' => $cycle,
                    'level' => $targetLevel,
                    'name' => $source->name,
                ];

                if (Subject::where($attributes)->exists()) {
                    continue;
                }

                try {
                    Subject::create($attributes + [
                        'domain' => $source->domain,
                        'coefficient' => $source->coefficient,
                        'max_score' => $source->max_score,
                        'sort_order' => $source->sort_order,
                        'is_active' => true,
                    ]);
                    $created++;
                } catch (QueryException) {
                    // Doublon concurrent : déjà présent, on l'ignore.
                }
            }
        }

        return redirect()->route('app.subjects.index')
            ->with('success', $created > 0
                ? "{$created} matière(s) copiée(s) vers ".implode(', ', $validated['target_levels'])." ({$currentYear->name})."
                : 'Rien à copier : ces matières existent déjà sur le(s) niveau(x) choisi(s).');
    }

    public function destroy(Subject $subject)
    {
        if ($subject->school_id !== session('current_school_id')) {
            abort(403);
        }

        $subject->delete();

        return redirect()->route('app.subjects.index')
            ->with('success', 'Matière supprimée !');
    }

    /** Niveaux proposés selon le type de l'école. */
    private function levelsForSchool(): array
    {
        $school = session('current_school');
        $levels = self::LEVELS_BY_CYCLE;

        if ($school && $school->school_type === 'maternelle') {
            unset($levels['primaire']);
        } elseif ($school && $school->school_type === 'primaire') {
            unset($levels['maternelle']);
        }

        return $levels;
    }

    /** Domaines déjà utilisés par l'école (suggestions de saisie). */
    private function knownDomains(int $schoolId): array
    {
        return Subject::where('school_id', $schoolId)
            ->whereNotNull('domain')
            ->distinct()
            ->orderBy('domain')
            ->pluck('domain')
            ->all();
    }
}
