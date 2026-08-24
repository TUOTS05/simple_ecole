<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Grade;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class GradeController extends Controller
{
    /**
     * Afficher la liste des classes assignées à l'enseignant, pour choisir laquelle noter
     */
    public function selectClass()
    {
        $teacher = auth()->user();
        $assignments = $teacher->teacherAssignments()
            ->with(['schoolClass' => function ($q) {
                $q->withCount('students');
            }])
            ->get();

        return view('teacher.grades.select-class', compact('assignments'));
    }

    /**
     * Afficher la liste des matières et l'historique des notes d'une classe
     */
    // public function index($classId)
    // {
    //     $teacher = auth()->user();
    //     $assignment = $teacher->teacherAssignments()->where('school_class_id', $classId)->firstOrFail();
    //     $class = $assignment->schoolClass;

    //     // 1. Matières de la classe (basé sur cycle et niveau, comme validé précédemment)
    //     $subjects = Subject::where('school_id', $class->school_id)
    //         ->where('cycle', $class->cycle)
    //         ->where('level', $class->level)
    //         ->where('is_active', true)
    //         ->orderBy('name')
    //         ->get();

    //     // 2. Historique des notes déjà saisies pour cette classe (pour que l'enseignant les voie)
    //     $recentGrades = Grade::where('school_class_id', $classId)
    //         ->where('marked_by', $teacher->id)
    //         ->with(['subject', 'student'])
    //         ->orderBy('created_at', 'desc')
    //         ->take(20) // Les 20 dernières saisies
    //         ->get();

    //     return view('teacher.grades.index', compact('class', 'subjects', 'recentGrades'));
    // }

     public function index($classId)
    {
        $teacher = auth()->user();
        $schoolYearId = SchoolYear::where('school_id', $teacher->school_id)->where('is_active', true)->value('id');
        $assignment = $teacher->teacherAssignments()
            ->where('school_class_id', $classId)
            ->where('school_year_id', $schoolYearId)
            ->firstOrFail();
        $class = $assignment->schoolClass;

        // 1. Matières de la classe
        $subjects = Subject::where('school_id', $class->school_id)
            ->where('cycle', $class->cycle)
            ->where('level', $class->level)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // 2. Filtres sélectionnés
        $selectedSubjectId = request('subject_id');
        $selectedPeriod = request('period', 'Trimestriel');
        $selectedQuarter = request('quarter');
        $selectedMonth = request('month');

        // 3. Élèves de la classe
        $students = $class->students()->where('status', 'active')->orderBy('last_name')->get();

        // 4. Notes existantes pour cette sélection (pour pré-remplir le tableau)
        $existingGrades = collect();
        if ($selectedSubjectId) {
            $query = Grade::where('school_class_id', $classId)
                ->where('subject_id', $selectedSubjectId)
                ->where('school_year_id', $schoolYearId)
                ->where('period', $selectedPeriod);
                
            if ($selectedPeriod === 'Trimestriel' && $selectedQuarter) {
                $query->where('quarter', $selectedQuarter);
            } elseif ($selectedPeriod === 'Mensuel' && $selectedMonth) {
                $query->where('month', $selectedMonth);
            }

            $existingGrades = $query->get()->keyBy('student_id');
        }

        return view('teacher.grades.index', compact(
            'class', 'subjects', 'students', 'existingGrades', 
            'selectedSubjectId', 'selectedPeriod', 'selectedQuarter', 'selectedMonth'
        ));
    }

    /**
     * Afficher le formulaire pour saisir les notes d'une matière
     */
    public function create($classId, $subjectId)
    {
        $teacher = auth()->user();
        $schoolYearId = SchoolYear::where('school_id', $teacher->school_id)->where('is_active', true)->value('id');
        $teacher->teacherAssignments()
            ->where('school_class_id', $classId)
            ->where('school_year_id', $schoolYearId)
            ->firstOrFail();

        $class = SchoolClass::findOrFail($classId);
        $subject = Subject::findOrFail($subjectId);

        $students = $class->students()
            ->where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // Récupérer l'année scolaire en cours (à adapter selon votre logique de session)
        $currentSchoolYear = SchoolYear::where('school_id', $class->school_id)->where('is_active', true)->first();

        return view('teacher.grades.create', compact('class', 'subject', 'students', 'currentSchoolYear'));
    }

    /**
     * Enregistrer les notes (Strictement aligné avec la migration 'grades')
     */
    // public function store(Request $request, $classId)
    // {
    //     $teacher = auth()->user();
    //     $teacher->teacherAssignments()->where('school_class_id', $classId)->firstOrFail();

    //     // Validation strictement basée sur les colonnes de votre table 'grades'
    //     $validated = $request->validate([
    //         'subject_id' => 'required|exists:subjects,id',
    //         'period' => 'required|in:Mensuel,Trimestriel',
    //         'month' => 'nullable|string|max:255',
    //         'quarter' => 'nullable|integer|in:1,2,3',
    //         'max_score' => 'required|numeric|min:1',
    //         'grades' => 'required|array',
    //         'grades.*.score' => 'nullable|numeric|min:0',
    //         'grades.*.remarks' => 'nullable|string|max:255', // Peut servir à mettre "Interrogation 1 : Bon effort"
    //     ]);

    //     // Récupérer l'ID de l'année scolaire en cours (adaptez si vous utilisez session('current_school_year_id'))
    //     $schoolYearId = SchoolYear::where('school_id', $teacher->school_id)->where('is_active', true)->value('id');

    //     DB::beginTransaction();
    //     try {
    //         $gradesData = [];
    //         foreach ($validated['grades'] as $studentId => $gradeData) {
    //             // On n'enregistre que si une note a été saisie
    //             if ($gradeData['score'] !== null && $gradeData['score'] !== '') {
    //                 $score = min((float)$gradeData['score'], (float)$validated['max_score']);

    //                 $gradesData[] = [
    //                     'school_id' => $teacher->school_id,
    //                     'student_id' => $studentId,
    //                     'subject_id' => $validated['subject_id'],
    //                     'school_class_id' => $classId,
    //                     'school_year_id' => $schoolYearId,
    //                     'period' => $validated['period'],
    //                     'month' => $validated['month'],
    //                     'quarter' => $validated['quarter'],
    //                     'score' => $score,
    //                     'max_score' => $validated['max_score'],
    //                     'remarks' => $gradeData['remarks'],
    //                     'marked_by' => $teacher->id,
    //                     'created_at' => now(),
    //                     'updated_at' => now(),
    //                 ];
    //             }
    //         }

    //         if (!empty($gradesData)) {
    //             Grade::insert($gradesData);
    //         }

    //         DB::commit();
    //         return redirect()->route('teacher.grades.index', $classId)
    //             ->with('success', '✅ Notes enregistrées avec succès dans la base de données !');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return back()->withErrors(['error' => 'Erreur : ' . $e->getMessage()])->withInput();
    //     }
    // }

    public function store(Request $request, $classId)
    {
        $teacher = auth()->user();
        $schoolYearId = SchoolYear::where('school_id', $teacher->school_id)->where('is_active', true)->value('id');
        $teacher->teacherAssignments()
            ->where('school_class_id', $classId)
            ->where('school_year_id', $schoolYearId)
            ->firstOrFail();

        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'period' => 'required|in:Mensuel,Trimestriel',
            'month' => 'nullable|string|max:255',
            'quarter' => 'nullable|integer|in:1,2,3',
            'max_score' => 'required|numeric|min:1',
            'grades' => 'required|array',
            'grades.*.score' => 'nullable|numeric|min:0',
            'grades.*.remarks' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['grades'] as $studentId => $gradeData) {
                // On ne traite que les lignes où une note est saisie
                if ($gradeData['score'] !== null && $gradeData['score'] !== '') {
                    $score = min((float)$gradeData['score'], (float)$validated['max_score']);
                    
                    // ✅ UPDATE OR CREATE : Met à jour si la combinaison existe, sinon crée.
                    Grade::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'subject_id' => $validated['subject_id'],
                            'school_class_id' => $classId,
                            'school_year_id' => $schoolYearId,
                            'period' => $validated['period'],
                            'quarter' => $validated['quarter'],
                            'month' => $validated['month'],
                        ],
                        [
                            'school_id' => $teacher->school_id,
                            'score' => $score,
                            'max_score' => $validated['max_score'],
                            'remarks' => $gradeData['remarks'],
                            'marked_by' => $teacher->id,
                        ]
                    );
                }
            }

            DB::commit();
            return redirect()->route('teacher.grades.index', $classId)
                ->with('success', '✅ Notes enregistrées ou mises à jour avec succès !');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur : ' . $e->getMessage()])->withInput();
        }
    }
}
