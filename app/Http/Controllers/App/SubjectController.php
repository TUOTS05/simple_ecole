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
        $subjects = collect($request->input('subjects', []))
            ->filter(function ($subject) {
                return ! empty($subject['name']) && ! empty($subject['coefficient']) && ! empty($subject['max_score']);
            })
            ->values()
            ->all();

        if (empty($subjects)) {
            return back()->withErrors(['subjects' => 'Veuillez ajouter au moins une matière valide.'])
                ->withInput();
        }

        $validated = $request->validate([
            'cycle' => 'required|in:maternelle,primaire',
            'level' => 'required|string|max:10',
            'subjects' => 'required|array|min:1',
            'subjects.*.name' => 'required|string|max:255',
            'subjects.*.coefficient' => 'required|numeric|min:1|max:10',
            'subjects.*.max_score' => 'required|numeric|in:10,20,40,50,100',
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
