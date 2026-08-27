<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

class GradeController extends Controller
{
    /**
     * Liste des bulletins d'un élève
     */
    // public function index($studentId)
    // {
    //     $parent = auth()->user();

    //     // Vérifier l'accès
    //     $student = $parent->children()
    //         ->where('students.id', $studentId)
    //         ->with('school')
    //         ->firstOrFail();

    //     // Récupérer tous les bulletins de cet élève
    //     $reportCards = ReportCard::where('student_id', $studentId)
    //         ->with(['schoolYear', 'schoolClass'])
    //         ->orderBy('school_year_id', 'desc')
    //         ->get();

    //     return view('parent.grades.index', compact('student', 'reportCards'));
    // }

    /**
     * Liste des bulletins d'un élève
     */
    public function index($studentId)
    {
        $parent = auth()->user();

        // 1. Vérifier l'accès
        $student = $parent->children()
            ->where('students.id', $studentId)
            ->with('school')
            ->firstOrFail();

        // ✅ 2. Récupérer TOUS les enfants pour le menu déroulant
        $siblings = $parent->children()->get();

        // 3. Récupérer tous les bulletins de cet élève
        $reportCards = ReportCard::where('student_id', $studentId)
            ->with(['schoolYear', 'schoolClass'])
            ->orderBy('school_year_id', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('parent.grades.index', compact('student', 'siblings', 'reportCards'));
    }

    /**
     * Télécharger un bulletin en PDF
     */
    public function downloadPdf($studentId, $reportCardId)
    {
        $parent = auth()->user();

        // 1. Vérifier l'accès : l'enfant doit appartenir à ce parent
        $student = $parent->children()
            ->where('students.id', $studentId)
            ->firstOrFail();

        // 2. Récupérer le bulletin avec les relations de base
        $reportCard = ReportCard::where('id', $reportCardId)
            ->where('student_id', $studentId)
            ->with(['student', 'schoolClass', 'schoolYear'])
            ->firstOrFail();

        // 3. Préparer les variables comme dans le contrôleur Admin
        $school = $student->school;
        $schoolYear = $reportCard->schoolYear;
        $schoolClass = $reportCard->schoolClass;

        // 4. Charger les notes manuellement selon les critères (insensible à la casse)
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

        // 5. Récupérer TOUTES les matières de la classe (pour afficher les '--' si pas de note)
        $allSubjects = Subject::where('school_id', $schoolClass->school_id)
            ->where('school_year_id', $reportCard->school_year_id)
            ->where('cycle', $schoolClass->cycle)
            ->where('level', $schoolClass->level)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // 6. Générer le PDF avec la vue partagée et TOUTES les variables nécessaires
        $pdf = Pdf::loadView('pdf.report-card', compact(
            'reportCard',
            'student',
            'school',
            'schoolYear',
            'schoolClass',
            'allSubjects'
        ));

        $filename = 'Bulletin_'.$student->last_name.'_'.$student->first_name.'_'.$reportCard->schoolYear->name.'.pdf';

        return $pdf->download($filename);
    }
}
