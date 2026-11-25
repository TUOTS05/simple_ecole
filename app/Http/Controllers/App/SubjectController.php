<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
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

        $subjects = $query->orderBy('cycle')->orderBy('level')->orderBy('name')->get();

        return view('app.subjects.index', compact('subjects'));
    }

    public function create()
    {
        $school = session('current_school');
        $currentYear = SchoolYear::where('school_id', session('current_school_id'))->where('is_active', true)->first();

        $levelsByCycle = [
            'maternelle' => ['TPS', 'PS', 'MS', 'GS'],
            'primaire' => ['CP', 'CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2'],
        ];

        // Matières par défaut selon le cycle
        $defaultSubjects = [
            'maternelle' => ['Lecture', 'Écriture/Copie', 'Orthographe', 'Grammaire/Conjugaison', 'Vocabulaire/EDHC', 'Exploitation de texte', 'Expression Écrite', 'Éveil du Milieu', 'Sciences', 'Mathématique', 'Chant/Récitation', 'Dessin', 'EPS', 'Dictée', 'Anglais'],
            'primaire' => ['Lecture', 'Écriture/Copie', 'Orthographe', 'Grammaire/Conjugaison', 'Vocabulaire/EDHC', 'Exploitation de texte', 'Expression Écrite', 'Éveil du Milieu', 'Sciences', 'Mathématique', 'Chant/Récitation', 'Dessin', 'EPS', 'Dictée', 'Anglais'],
        ];

        return view('app.subjects.create', compact('school', 'currentYear', 'levelsByCycle', 'defaultSubjects'));
    }

    // public function store(Request $request)
    // {

    //     $validated = $request->validate([
    //         'cycle' => 'required|in:maternelle,primaire',
    //         'level' => 'required|string|max:10',
    //         'subjects' => 'required|array',
    //         'subjects.*.name' => 'required|string|max:255',
    //         'subjects.*.coefficient' => 'required|numeric|min:1|max:10',
    //     ]);

    //     dd($validated);

    //     $schoolId = session('current_school_id');
    //     $currentYear = SchoolYear::where('school_id', $schoolId)->where('is_active', true)->first();

    //     foreach ($validated['subjects'] as $subjectData) {
    //         Subject::updateOrCreate(
    //             [
    //                 'school_id' => $schoolId,
    //                 'school_year_id' => $currentYear?->id,
    //                 'cycle' => $validated['cycle'],
    //                 'level' => $validated['level'],
    //                 'name' => $subjectData['name'],
    //             ],
    //             [
    //                 'coefficient' => $subjectData['coefficient'],
    //                 'is_active' => true,
    //             ]
    //         );
    //     }

    //     return redirect()->route('app.subjects.index')
    //         ->with('success', 'Matières enregistrées avec succès !');
    // }

    //     public function store(Request $request)
    // {
    //     // Filtrer les entrées vides
    //     $subjects = collect($request->input('subjects', []))
    //         ->filter(function($subject) {
    //             return !empty($subject['name']) && !empty($subject['coefficient']);
    //         })
    //         ->values()
    //         ->all();

    //     if (empty($subjects)) {
    //         return back()->withErrors(['subjects' => 'Veuillez ajouter au moins une matière valide.'])
    //             ->withInput();
    //     }

    //     $validated = $request->validate([
    //         'cycle' => 'required|in:maternelle,primaire',
    //         'level' => 'required|string|max:10',
    //         'subjects' => 'required|array|min:1',
    //         'subjects.*.name' => 'required|string|max:255',
    //         'subjects.*.coefficient' => 'required|numeric|min:1|max:10',
    //     ]);

    //     $schoolId = session('current_school_id');
    //     $currentYear = SchoolYear::where('school_id', $schoolId)->where('is_active', true)->first();

    //     if (!$currentYear) {
    //         return back()->withErrors(['error' => 'Aucune année scolaire active trouvée.'])
    //             ->withInput();
    //     }

    //     $count = 0;
    //     foreach ($validated['subjects'] as $subjectData) {
    //         if (empty($subjectData['name'])) continue;

    //         Subject::updateOrCreate(
    //             [
    //                 'school_id' => $schoolId,
    //                 'school_year_id' => $currentYear->id,
    //                 'cycle' => $validated['cycle'],
    //                 'level' => $validated['level'],
    //                 'name' => $subjectData['name'],
    //             ],
    //             [
    //                 'coefficient' => $subjectData['coefficient'],
    //                 'is_active' => true,
    //             ]
    //         );
    //         $count++;
    //     }

    //     return redirect()->route('app.subjects.index')
    //         ->with('success', "{$count} matière(s) enregistrée(s) avec succès !");
    // }

    public function store(Request $request)
    {
        // On ignore les lignes laissées vides (sans nom) et on complète les valeurs
        // par défaut, puis on valide CES données filtrées (et non la requête brute,
        // sinon une ligne vide fait échouer tout l'enregistrement).
        $subjects = collect($request->input('subjects', []))
            ->filter(fn ($subject) => is_array($subject) && trim((string) ($subject['name'] ?? '')) !== '')
            ->map(fn ($subject) => [
                'name' => trim($subject['name']),
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
            'subjects.*.coefficient' => 'required|numeric|min:1|max:10',
            'subjects.*.max_score' => 'required|numeric|in:10,20,40,50,100',
        ], [
            'cycle.required' => 'Veuillez sélectionner un cycle.',
            'cycle.in' => 'Le cycle sélectionné est invalide.',
            'level.required' => 'Veuillez sélectionner un niveau.',
            'subjects.*.name.max' => 'Le nom de la matière n° :position est trop long (255 caractères max).',
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

        $count = 0;
        foreach ($validated['subjects'] as $subjectData) {
            if (empty($subjectData['name'])) {
                continue;
            }

            Subject::updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'school_year_id' => $currentYear->id,
                    'cycle' => $validated['cycle'],
                    'level' => $validated['level'],
                    'name' => $subjectData['name'],
                ],
                [
                    'coefficient' => $subjectData['coefficient'],
                    'max_score' => $subjectData['max_score'],
                    'is_active' => true,
                ]
            );
            $count++;
        }

        return redirect()->route('app.subjects.index')
            ->with('success', "{$count} matière(s) enregistrée(s) avec succès !");
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
}
