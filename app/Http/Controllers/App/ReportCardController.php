<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\ReportCard;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportCardController extends Controller
{
    // public function index(Request $request)
    // {
    //     $schoolId = session('current_school_id');
    //     $currentYear = \App\Models\SchoolYear::where('school_id', $schoolId)
    //         ->where('is_active', true)
    //         ->first();

    //     $reportCards = ReportCard::where('school_id', $schoolId)
    //         ->where('school_year_id', $currentYear?->id)
    //         ->with(['student', 'schoolClass'])
    //         ->orderBy('created_at', 'desc')
    //         ->paginate(15);

    //     return view('app.report-cards.index', compact('reportCards'));
    // }

        public function index(Request $request)
    {
        $schoolId = session('current_school_id');
        $currentYear = \App\Models\SchoolYear::where('school_id', $schoolId)
            ->where('is_active', true)
            ->first();

        // 1. Démarrer la requête avec les relations nécessaires
        $query = ReportCard::where('school_id', $schoolId)
            ->where('school_year_id', $currentYear?->id)
            ->with(['student', 'schoolClass', 'schoolYear']);

        // 2. Appliquer les filtres dynamiquement
        if ($request->filled('period')) {
            $query->where('period', $request->period);
        }

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        if ($request->filled('quarter')) {
            $query->where('quarter', $request->quarter);
        }

        // 3. Trier et paginer les résultats
        $reportCards = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('app.report-cards.index', compact('reportCards'));
    }


    // public function create(Request $request)
    // {
    //     $schoolId = session('current_school_id');
    //     $currentYear = \App\Models\SchoolYear::where('school_id', $schoolId)
    //         ->where('is_active', true)
    //         ->first();

    //     $classes = SchoolClass::where('school_id', $schoolId)->orderBy('name')->get();
    //     $selectedClassId = $request->get('class_id');
    //     $period = $request->get('period', 'mensuel');
    //     $month = $request->get('month', now()->format('F'));
    //     $quarter = $request->get('quarter', 1);

    //     $students = collect();
    //     $subjects = collect();
    //     $levelMismatch = false;

    //     if ($selectedClassId) {
    //         $class = SchoolClass::find($selectedClassId);
    //         $students = $class->students()->where('status', 'active')->orderBy('last_name')->get();

    //         // Recherche EXACTE des matières pour ce cycle et ce niveau
    //         $subjects = Subject::where('school_id', $schoolId)
    //             ->where('school_year_id', $currentYear?->id)
    //             ->where('cycle', $class->cycle)
    //             ->where('level', $class->level) // Doit correspondre exactement à la casse
    //             ->where('is_active', true)
    //             ->orderBy('name')
    //             ->get();

    //         // Diagnostic : si aucune matière n'est trouvée, vérifier pourquoi
    //         if ($subjects->isEmpty() && $students->isNotEmpty()) {
    //             $subjectExistsForCycle = Subject::where('school_id', $schoolId)
    //                 ->where('school_year_id', $currentYear?->id)
    //                 ->where('cycle', $class->cycle)
    //                 ->exists();

    //             $levelMismatch = !$subjectExistsForCycle;
    //         }
    //     }

    //     return view('app.report-cards.create', compact(
    //         'classes',
    //         'students',
    //         'subjects',
    //         'selectedClassId',
    //         'period',
    //         'month',
    //         'quarter',
    //         'levelMismatch'
    //     ));
    // }


    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'class_id' => 'required|exists:school_classes,id',
    //         'period' => 'required|in:mensuel,trimestriel',
    //         'month' => 'nullable|string',
    //         'quarter' => 'nullable|integer|min:1|max:3',
    //         'coefficients' => 'required|array',
    //         'coefficients.*' => 'required|numeric|min:1|max:10',
    //         'grades' => 'required|array',
    //         'grades.*.*.score' => 'required|numeric|min:0|max:100',
    //         'grades.*.*.max_score' => 'required|numeric|min:1',
    //         'grades.*.*.remarks' => 'nullable|string|max:255',
    //     ]);

    //     $schoolId = session('current_school_id');
    //     $currentYear = \App\Models\SchoolYear::where('school_id', $schoolId)
    //         ->where('is_active', true)
    //         ->first();

    //     $class = SchoolClass::find($validated['class_id']);

    //     DB::beginTransaction();
    //     try {
    //         foreach ($validated['grades'] as $studentId => $subjectsData) {

    //             foreach ($subjectsData as $subjectId => $data) {
    //                 Grade::updateOrCreate(
    //                     [
    //                         'school_id' => $schoolId,
    //                         'student_id' => $studentId,
    //                         'subject_id' => $subjectId,
    //                         'school_class_id' => $validated['class_id'],
    //                         'school_year_id' => $currentYear?->id,
    //                         'period' => $validated['period'],
    //                         'month' => $validated['period'] === 'mensuel' ? $validated['month'] : null,
    //                         'quarter' => $validated['period'] === 'trimestriel' ? $validated['quarter'] : null,
    //                     ],
    //                     [
    //                         'score' => $data['score'],
    //                         'max_score' => $data['max_score'],
    //                         'coefficient_used' => $validated['coefficients'][$subjectId] ?? 1,
    //                         'remarks' => $data['remarks'] ?? null,
    //                         'marked_by' => auth()->id(),
    //                     ]
    //                 );
    //             }

    //             $student = Student::find($studentId);
    //             $this->calculateAndCreateReportCard(
    //                 $schoolId,
    //                 $student,
    //                 $validated['class_id'],
    //                 $currentYear?->id,
    //                 $validated['period'],
    //                 $validated['month'] ?? null,
    //                 $validated['quarter'] ?? null
    //             );
    //         }

    //         DB::commit();

    //         return redirect()->route('app.report-cards.index')
    //             ->with('success', 'Notes enregistrées et bulletins générés avec succès !');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return back()->withErrors(['error' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()]);
    //     }
    // }


    public function create(Request $request)
    {
        $schoolId = session('current_school_id');
        $currentYear = \App\Models\SchoolYear::where('school_id', $schoolId)->where('is_active', true)->first();

        $classes = SchoolClass::where('school_id', $schoolId)->orderBy('name')->get();
        $selectedClassId = $request->get('class_id');

        // Normalisation : on s'assure que la période correspond à celle de l'enseignant (Majuscule)
        $period = ucfirst($request->get('period', 'mensuel'));
        $month = ucfirst($request->get('month', '')); // Ex: "Juillet"
        $quarter = $request->get('quarter', 1);

        $students = collect();
        $subjects = collect();
        $levelMismatch = false;
        $existingGrades = collect();

        if ($selectedClassId) {
            $class = SchoolClass::find($selectedClassId);
            $students = $class->students()->where('status', 'active')->orderBy('last_name')->get();

            $subjects = Subject::where('school_id', $schoolId)
                ->where('school_year_id', $currentYear?->id)
                ->where('cycle', $class->cycle)
                ->where('level', $class->level)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            // ✅ CHARGEMENT DES NOTES EXISTANTES (saisies par l'enseignant)
            $gradesQuery = Grade::where('school_class_id', $selectedClassId)
                ->where('school_year_id', $currentYear?->id)
                ->where('period', $period); // "Mensuel" ou "Trimestriel"

            if ($period === 'Mensuel') {
                $gradesQuery->where('month', $month);
            } else {
                $gradesQuery->where('quarter', $quarter);
            }

            // Organisation des notes par élève, puis par matière
            $existingGrades = $gradesQuery->get()->groupBy('student_id')->map(function ($studentGrades) {
                return $studentGrades->keyBy('subject_id');
            });
        }

        return view('app.report-cards.create', compact(
            'classes',
            'students',
            'subjects',
            'selectedClassId',
            'period',
            'month',
            'quarter',
            'levelMismatch',
            'existingGrades'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            // ✅ Tolérance sur la casse (Mensuel ou mensuel)
            'period' => 'required|in:Mensuel,mensuel,Trimestriel,trimestriel',
            'month' => 'nullable|string',
            'quarter' => 'nullable|integer|min:1|max:3',
            'coefficients' => 'nullable|array', // ✅ Rendu optionnel
            'coefficients.*' => 'nullable|numeric|min:1|max:10',
            'grades' => 'required|array',
            'grades.*.*.score' => 'nullable|numeric|min:0|max:100', // ✅ Nullable pour permettre des champs vides
            'grades.*.*.max_score' => 'required|numeric|min:1',
            'grades.*.*.remarks' => 'nullable|string|max:255',
        ]);

        $schoolId = session('current_school_id');
        $currentYear = \App\Models\SchoolYear::where('school_id', $schoolId)->where('is_active', true)->first();
        $class = SchoolClass::find($validated['class_id']);

        // Normalisation des données pour la BDD
        $normalizedPeriod = ucfirst($validated['period']); // Force "Mensuel" ou "Trimestriel"
        $normalizedMonth = $normalizedPeriod === 'Mensuel' ? ucfirst($validated['month']) : null;

        DB::beginTransaction();
        try {
            foreach ($validated['grades'] as $studentId => $subjectsData) {
                foreach ($subjectsData as $subjectId => $data) {
                    // On n'enregistre que si une note est saisie
                    if (!empty($data['score'])) {
                        // Récupérer le coefficient (du formulaire ou de la matière)
                        $subject = Subject::find($subjectId);
                        $coef = $validated['coefficients'][$subjectId] ?? ($subject->coefficient ?? 1);

                        Grade::updateOrCreate(
                            [
                                'school_id' => $schoolId,
                                'student_id' => $studentId,
                                'subject_id' => $subjectId,
                                'school_class_id' => $validated['class_id'],
                                'school_year_id' => $currentYear?->id,
                                'period' => $normalizedPeriod,
                                'month' => $normalizedMonth,
                                'quarter' => $normalizedPeriod === 'Trimestriel' ? $validated['quarter'] : null,
                            ],
                            [
                                'score' => $data['score'],
                                'max_score' => $data['max_score'],
                                'coefficient_used' => $coef,
                                'remarks' => $data['remarks'] ?? null,
                                'marked_by' => auth()->id(),
                            ]
                        );
                    }
                }

                $student = Student::find($studentId);
                $this->calculateAndCreateReportCard(
                    $schoolId,
                    $student,
                    $validated['class_id'],
                    $currentYear?->id,
                    $normalizedPeriod,
                    $normalizedMonth,
                    $validated['quarter'] ?? null
                );
            }

            DB::commit();
            return redirect()->route('app.report-cards.index')
                ->with('success', 'Notes enregistrées et bulletins générés avec succès !');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur : ' . $e->getMessage()]);
        }
    }

    // private function calculateAndCreateReportCard($schoolId, $student, $classId, $yearId, $period, $month, $quarter)
    // {
    //     $gradesQuery = Grade::where('school_id', $schoolId)
    //         ->where('student_id', $student->id)
    //         ->where('school_class_id', $classId)
    //         ->where('school_year_id', $yearId)
    //         ->where('period', $period);

    //     if ($period === 'mensuel') {
    //         $gradesQuery->where('month', $month);
    //     } else {
    //         $gradesQuery->where('quarter', $quarter);
    //     }

    //     $grades = $gradesQuery->with('subject')->get();

    //     if ($grades->isEmpty()) {
    //         return;
    //     }

    //     $totalWeightedScore = 0;
    //     $totalCoefficients = 0;

    //     foreach ($grades as $grade) {
    //         $score = $grade->score;
    //         $maxScore = $grade->subject->max_score ?? $grade->max_score ?? 20;
    //         $scoreOutOf20 = ($score / $maxScore) * 20;
    //         $coefficient = $grade->coefficient_used ?? $grade->subject->coefficient ?? 1;

    //         $totalWeightedScore += ($scoreOutOf20 * $coefficient);
    //         $totalCoefficients += $coefficient;
    //     }

    //     $average = $totalCoefficients > 0 ? $totalWeightedScore / $totalCoefficients : 0;

    //     $allAverages = $this->getAllStudentAverages($schoolId, $classId, $yearId, $period, $month, $quarter);
    //     $rank = 1;
    //     foreach ($allAverages as $avg) {
    //         if ($avg > $average) {
    //             $rank++;
    //         }
    //     }

    //     ReportCard::updateOrCreate(
    //         [
    //             'school_id' => $schoolId,
    //             'student_id' => $student->id,
    //             'school_year_id' => $yearId,
    //             'school_class_id' => $classId,
    //             'period' => $period,
    //             'month' => $period === 'mensuel' ? $month : null,
    //             'quarter' => $period === 'trimestriel' ? $quarter : null,
    //         ],
    //         [
    //             'average' => round($average, 2),
    //             'rank' => $rank,
    //             'total_students' => count($allAverages),
    //             'created_by' => auth()->id(),
    //         ]
    //     );
    // }

    // private function getAllStudentAverages($schoolId, $classId, $yearId, $period, $month, $quarter)
    // {
    //     $averages = [];

    //     $students = Student::where('school_id', $schoolId)
    //         ->where('status', 'active')
    //         ->whereHas('classes', fn($q) => $q->where('school_classes.id', $classId))
    //         ->get();

    //     foreach ($students as $student) {
    //         $grades = Grade::where('school_id', $schoolId)
    //             ->where('student_id', $student->id)
    //             ->where('school_class_id', $classId)
    //             ->where('school_year_id', $yearId)
    //             ->where('period', $period);

    //         if ($period === 'mensuel') {
    //             $grades->where('month', $month);
    //         } else {
    //             $grades->where('quarter', $quarter);
    //         }

    //         $grades = $grades->with('subject')->get();

    //         if ($grades->isEmpty()) {
    //             continue;
    //         }

    //         $totalWeightedScore = 0;
    //         $totalCoefficients = 0;

    //         foreach ($grades as $grade) {
    //             $scoreOutOf20 = $grade->score_out_of_20;
    //             $coefficient = $grade->subject->coefficient ?? 1;
    //             $totalWeightedScore += ($scoreOutOf20 * $coefficient);
    //             $totalCoefficients += $coefficient;
    //         }

    //         if ($totalCoefficients > 0) {
    //             $averages[] = $totalWeightedScore / $totalCoefficients;
    //         }
    //     }

    //     return $averages;
    // }


    private function calculateAndCreateReportCard($schoolId, $student, $classId, $yearId, $period, $month, $quarter)
    {
        $gradesQuery = Grade::where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->where('school_class_id', $classId)
            ->where('school_year_id', $yearId)
            ->where('period', $period);

        // ✅ CORRECTION : Utilisation de strtolower pour que "Mensuel" === "mensuel" fonctionne
        if (strtolower($period) === 'mensuel') {
            $gradesQuery->where('month', $month);
        } else {
            $gradesQuery->where('quarter', $quarter);
        }

        $grades = $gradesQuery->with('subject')->get();

        if ($grades->isEmpty()) {
            return;
        }

        $totalWeightedScore = 0;
        $totalCoefficients = 0;

        foreach ($grades as $grade) {
            $score = $grade->score;
            $maxScore = $grade->subject->max_score ?? $grade->max_score ?? 20;
            $scoreOutOf20 = ($score / $maxScore) * 20;
            $coefficient = $grade->coefficient_used ?? $grade->subject->coefficient ?? 1;

            $totalWeightedScore += ($scoreOutOf20 * $coefficient);
            $totalCoefficients += $coefficient;
        }

        $average = $totalCoefficients > 0 ? $totalWeightedScore / $totalCoefficients : 0;

        $allAverages = $this->getAllStudentAverages($schoolId, $classId, $yearId, $period, $month, $quarter);
        $rank = 1;
        foreach ($allAverages as $avg) {
            if ($avg > $average) {
                $rank++;
            }
        }

        ReportCard::updateOrCreate(
            [
                'school_id' => $schoolId,
                'student_id' => $student->id,
                'school_year_id' => $yearId,
                'school_class_id' => $classId,
                'period' => $period,
                // ✅ CORRECTION CRITIQUE : strtolower pour enregistrer correctement le mois ou le trimestre
                'month' => strtolower($period) === 'mensuel' ? $month : null,
                'quarter' => strtolower($period) === 'trimestriel' ? $quarter : null,
            ],
            [
                'average' => round($average, 2),
                'rank' => $rank,
                'total_students' => count($allAverages),
                'created_by' => auth()->id(),
            ]
        );
    }

    private function getAllStudentAverages($schoolId, $classId, $yearId, $period, $month, $quarter)
    {
        $averages = [];

        $students = Student::where('school_id', $schoolId)
            ->where('status', 'active')
            ->whereHas('classes', fn($q) => $q->where('school_classes.id', $classId))
            ->get();

        foreach ($students as $student) {
            $grades = Grade::where('school_id', $schoolId)
                ->where('student_id', $student->id)
                ->where('school_class_id', $classId)
                ->where('school_year_id', $yearId)
                ->where('period', $period);

            // ✅ CORRECTION : strtolower ici aussi
            if (strtolower($period) === 'mensuel') {
                $grades->where('month', $month);
            } else {
                $grades->where('quarter', $quarter);
            }

            $grades = $grades->with('subject')->get();

            if ($grades->isEmpty()) {
                continue;
            }

            $totalWeightedScore = 0;
            $totalCoefficients = 0;

            foreach ($grades as $grade) {
                $maxScore = $grade->subject->max_score ?? $grade->max_score ?? 20;
                $scoreOutOf20 = ($grade->score / $maxScore) * 20;
                $coefficient = $grade->coefficient_used ?? $grade->subject->coefficient ?? 1;

                $totalWeightedScore += ($scoreOutOf20 * $coefficient);
                $totalCoefficients += $coefficient;
            }

            if ($totalCoefficients > 0) {
                $averages[] = $totalWeightedScore / $totalCoefficients;
            }
        }

        return $averages;
    }


    // public function show(ReportCard $reportCard)
    // {
    //     if ($reportCard->school_id !== session('current_school_id')) {
    //         abort(403);
    //     }

    //     $reportCard->load([
    //         'student',
    //         'schoolClass',
    //         'schoolYear',
    //         'createdBy'
    //     ]);

    //     // Charger les notes manuellement selon les critères
    //     $gradesQuery = Grade::where('school_id', $reportCard->school_id)
    //         ->where('student_id', $reportCard->student_id)
    //         ->where('school_year_id', $reportCard->school_year_id)
    //         ->where('period', $reportCard->period)
    //         ->with('subject');

    //     if (strtolower($reportCard->period) === 'mensuel') {
    //         $gradesQuery->where('month', $reportCard->month);
    //     } else {
    //         $gradesQuery->where('quarter', $reportCard->quarter);
    //     }

    //     $grades = $gradesQuery->get();

    //     // Attacher les notes au bulletin
    //     $reportCard->setRelation('grades', $grades);

    //     return view('app.report-cards.show', compact('reportCard'));
    // }


    public function show(ReportCard $reportCard)
    {
        if ($reportCard->school_id !== session('current_school_id')) {
            abort(403);
        }

        $reportCard->load(['student', 'schoolClass', 'schoolYear', 'createdBy']);
        $schoolClass = $reportCard->schoolClass;

        // Charger les notes
        $gradesQuery = Grade::where('school_id', $reportCard->school_id)
            ->where('student_id', $reportCard->student_id)
            ->where('school_year_id', $reportCard->school_year_id)
            ->where('period', $reportCard->period)
            ->with('subject');

        if (strtolower($reportCard->period) === 'mensuel') {
            $gradesQuery->where('month', $reportCard->month);
        } else {
            $gradesQuery->where('quarter', $reportCard->quarter);
        }

        $grades = $gradesQuery->get();
        $reportCard->setRelation('grades', $grades);

        // ✅ AJOUT CRITIQUE : Charger toutes les matières pour que l'écran soit identique au PDF
        $allSubjects = Subject::where('school_id', $schoolClass->school_id)
            ->where('school_year_id', $reportCard->school_year_id)
            ->where('cycle', $schoolClass->cycle)
            ->where('level', $schoolClass->level)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // ✅ On passe maintenant $allSubjects à la vue
        return view('app.report-cards.show', compact('reportCard', 'allSubjects'));
    }

    public function edit(ReportCard $reportCard)
    {
        if ($reportCard->school_id !== session('current_school_id')) {
            abort(403);
        }

        $schoolId = session('current_school_id');
        $currentYear = \App\Models\SchoolYear::where('school_id', $schoolId)
            ->where('is_active', true)
            ->first();

        $classes = SchoolClass::where('school_id', $schoolId)->orderBy('name')->get();

        // Charger les notes existantes
        $gradesQuery = Grade::where('school_id', $schoolId)
            ->where('student_id', $reportCard->student_id)
            ->where('school_class_id', $reportCard->school_class_id)
            ->where('school_year_id', $reportCard->school_year_id)
            ->where('period', $reportCard->period)
            ->with('subject');

        if (strtolower($reportCard->period) === 'mensuel') {
            $gradesQuery->where('month', $reportCard->month);
        } else {
            $gradesQuery->where('quarter', $reportCard->quarter);
        }

        $existingGrades = $gradesQuery->get()->keyBy('subject_id');

        // Récupérer les matières pour cette classe
        $class = SchoolClass::find($reportCard->school_class_id);
        $subjects = Subject::where('school_id', $schoolId)
            ->where('school_year_id', $currentYear?->id)
            ->where('cycle', $class->cycle)
            ->where('level', $class->level)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $students = $class->students()->where('status', 'active')->orderBy('last_name')->get();

        return view('app.report-cards.edit', compact(
            'reportCard',
            'classes',
            'students',
            'subjects',
            'existingGrades'
        ));
    }

    public function update(Request $request, ReportCard $reportCard)
    {
        if ($reportCard->school_id !== session('current_school_id')) {
            abort(403);
        }

        $validated = $request->validate([
            'grades' => 'required|array',
            'grades.*.score' => 'required|numeric|min:0|max:100',
            'grades.*.max_score' => 'required|numeric|min:1',
            'grades.*.remarks' => 'nullable|string|max:255',
        ]);

        $schoolId = session('current_school_id');
        $currentYear = \App\Models\SchoolYear::where('school_id', $schoolId)
            ->where('is_active', true)
            ->first();

        DB::beginTransaction();
        try {
            // Mettre à jour chaque note
            foreach ($validated['grades'] as $subjectId => $data) {
                Grade::updateOrCreate(
                    [
                        'school_id' => $schoolId,
                        'student_id' => $reportCard->student_id,
                        'subject_id' => $subjectId,
                        'school_class_id' => $reportCard->school_class_id,
                        'school_year_id' => $reportCard->school_year_id,
                        'period' => $reportCard->period,
                        'month' => strtolower($reportCard->period) === 'mensuel' ? $reportCard->month : null,
                        'quarter' => strtolower($reportCard->period) === 'trimestriel' ? $reportCard->quarter : null,
                    ],
                    [
                        'score' => $data['score'],
                        'max_score' => $data['max_score'],
                        'remarks' => $data['remarks'] ?? null,
                        'marked_by' => auth()->id(),
                    ]
                );
            }

            // Recalculer la moyenne et mettre à jour le bulletin
            $this->calculateAndCreateReportCard(
                $schoolId,
                $reportCard->student,
                $reportCard->school_class_id,
                $reportCard->school_year_id,
                $reportCard->period,
                $reportCard->month,
                $reportCard->quarter
            );

            DB::commit();

            return redirect()->route('app.report-cards.show', $reportCard)
                ->with('success', 'Notes mises à jour avec succès !');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()]);
        }
    }

    public function destroy(ReportCard $reportCard)
    {
        if ($reportCard->school_id !== session('current_school_id')) {
            abort(403);
        }

        $reportCard->delete();

        return redirect()->route('app.report-cards.index')
            ->with('success', 'Bulletin supprimé !');
    }

    /**
     * Télécharger le bulletin en PDF
     */
    // public function downloadPdf(ReportCard $reportCard)
    // {
    //     // Sécurité : vérifier que le bulletin appartient à l'école connectée
    //     if ($reportCard->school_id !== session('current_school_id')) {
    //         abort(403);
    //     }

    //     // 1. Charger les relations de base
    //     $reportCard->load([
    //         'student',
    //         'schoolClass',
    //         'schoolYear',
    //         'createdBy'
    //     ]);

    //     $student = $reportCard->student;
    //     $school = $student->school ?? $reportCard->school;
    //     $schoolYear = $reportCard->schoolYear;
    //     $schoolClass = $reportCard->schoolClass;

    //     // 2. Charger les notes manuellement selon les critères (EXACTEMENT comme dans show())
    //     $gradesQuery = Grade::where('school_id', $reportCard->school_id)
    //         ->where('student_id', $reportCard->student_id)
    //         ->where('school_year_id', $reportCard->school_year_id)
    //         ->where('period', $reportCard->period)
    //         ->with('subject');

    //     if ($reportCard->period === 'mensuel') {
    //         $gradesQuery->where('month', $reportCard->month);
    //     } else {
    //         $gradesQuery->where('quarter', $reportCard->quarter);
    //     }

    //     $grades = $gradesQuery->get();

    //     // Attacher les notes au bulletin pour que la vue PDF puisse les lire
    //     $reportCard->setRelation('grades', $grades);

    //     // 3. Charger la vue PDF avec TOUTES les variables nécessaires
    //     $pdf = Pdf::loadView('pdf.report-card', compact(
    //         'reportCard',
    //         'student',
    //         'school',
    //         'schoolYear',
    //         'schoolClass'
    //     ));

    //     // 4. Configurer le format du PDF (A4, portrait)
    //     $pdf->setPaper('a4', 'portrait');

    //     // 5. Générer le téléchargement avec un nom de fichier propre
    //     $fileName = 'Bulletin_' . $student->last_name . '_' . $student->first_name . '_' . $reportCard->period . '.pdf';

    //     return $pdf->download($fileName);
    // }


    public function downloadPdf(ReportCard $reportCard)
    {
        if ($reportCard->school_id !== session('current_school_id')) {
            abort(403);
        }

        $reportCard->load(['student', 'schoolClass', 'schoolYear', 'createdBy']);
        $student = $reportCard->student;
        $school = $student->school ?? $reportCard->school;
        $schoolYear = $reportCard->schoolYear;
        $schoolClass = $reportCard->schoolClass;

        // 1. Charger les notes existantes
        $gradesQuery = Grade::where('school_id', $reportCard->school_id)
            ->where('student_id', $reportCard->student_id)
            ->where('school_year_id', $reportCard->school_year_id)
            ->where('period', $reportCard->period)
            ->with('subject');

        if (strtolower($reportCard->period) === 'mensuel') {
            $gradesQuery->where('month', $reportCard->month);
        } else {
            $gradesQuery->where('quarter', $reportCard->quarter);
        }

        $grades = $gradesQuery->get()->keyBy('subject_id');
        $reportCard->setRelation('grades', $grades->values());

        // 2. ✅ NOUVEAU : Récupérer TOUTES les matières de la classe
        $allSubjects = Subject::where('school_id', $schoolClass->school_id)
            ->where('school_year_id', $reportCard->school_year_id)
            ->where('cycle', $schoolClass->cycle)
            ->where('level', $schoolClass->level)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $pdf = Pdf::loadView('pdf.report-card', compact(
            'reportCard',
            'student',
            'school',
            'schoolYear',
            'schoolClass',
            'allSubjects'
        ));

        $pdf->setPaper('a4', 'portrait');
        $fileName = 'Bulletin_' . $student->last_name . '_' . $student->first_name . '_' . $reportCard->period . '.pdf';

        return $pdf->download($fileName);
    }


        /**
     * Télécharger tous les bulletins d'une classe en un seul PDF
     * (Filtres appliqués : period, month, quarter - comme dans la méthode index)
     */
    public function downloadClassBulk(Request $request)
    {
        $schoolId = session('current_school_id');
        $currentYear = \App\Models\SchoolYear::where('school_id', $schoolId)
            ->where('is_active', true)
            ->first();

        if (!$currentYear) {
            return back()->with('error', 'Aucune année scolaire active trouvée.');
        }

        // 1. Construire la requête avec les mêmes filtres que la page index
        $query = \App\Models\ReportCard::where('school_id', $schoolId)
            ->where('school_year_id', $currentYear->id)
            ->with(['student', 'schoolClass', 'schoolYear']);

        // 2. Appliquer les filtres (identiques à la méthode index)
        if ($request->filled('period')) {
            $query->where('period', $request->period);
        }
        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }
        if ($request->filled('quarter')) {
            $query->where('quarter', $request->quarter);
        }
        if ($request->filled('class_id')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->whereHas('enrollments', function($q2) use ($request) {
                    $q2->where('school_class_id', $request->class_id)
                       ->where('school_year_id', $currentYear->id);
                });
            });
        }

        $reportCards = $query->orderBy('school_class_id')->orderBy('student_id')->get();

        if ($reportCards->isEmpty()) {
            return back()->with('error', 'Aucun bulletin trouvé pour ces critères.');
        }

        // 3. Préparer les données pour chaque bulletin (même logique que downloadPdf)
        $allData = [];
        foreach ($reportCards as $reportCard) {
            $student = $reportCard->student;
            $school = \App\Models\School::find($schoolId);
            $schoolYear = $reportCard->schoolYear;
            $schoolClass = $reportCard->schoolClass;

            // Charger les notes selon les critères (logique identique à downloadPdf)
            $gradesQuery = \App\Models\Grade::where('school_id', $schoolId)
                ->where('student_id', $student->id)
                ->where('school_year_id', $currentYear->id)
                ->where('period', $reportCard->period)
                ->with('subject');

            if (strtolower($reportCard->period) === 'mensuel') {
                $gradesQuery->where('month', $reportCard->month);
            } else {
                $gradesQuery->where('quarter', $reportCard->quarter);
            }

            $grades = $gradesQuery->get();
            $reportCard->setRelation('grades', $grades);

            // Récupérer toutes les matières de la classe
            $allSubjects = \App\Models\Subject::where('school_id', $schoolId)
                ->where('school_year_id', $currentYear->id)
                ->where('cycle', $schoolClass->cycle)
                ->where('level', $schoolClass->level)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            $allData[] = compact('reportCard', 'student', 'school', 'schoolYear', 'schoolClass', 'allSubjects');
        }

        // 4. Générer le PDF avec la vue bulk
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.report-cards-bulk', [
            'allData' => $allData,
            'currentYear' => $currentYear,
        ]);

        // 5. Nom du fichier basé sur les filtres
        $filename = 'Bulletins';
        if ($request->filled('class_id')) {
            $class = \App\Models\SchoolClass::find($request->class_id);
            $filename .= '_' . ($class->name ?? 'Classe');
        }
        if ($request->filled('period')) {
            $filename .= '_' . $request->period;
            if ($request->period === 'Mensuel' && $request->filled('month')) {
                $filename .= '_' . $request->month;
            } elseif ($request->filled('quarter')) {
                $filename .= '_T' . $request->quarter;
            }
        }
        $filename .= '_' . $currentYear->name . '.pdf';

        return $pdf->download($filename);
    }
}
