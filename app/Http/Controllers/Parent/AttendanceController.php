<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

/**
 * Espace parent : présences et absences de ses enfants.
 */
class AttendanceController extends Controller
{
    /**
     * Présences d'un enfant du parent, sur une période (30 derniers jours par défaut).
     */
    public function index(Request $request, $studentId)
    {
        $parent = auth()->user();

        // 1. L'enfant demandé doit être celui du parent connecté
        $student = $parent->children()->where('students.id', $studentId)->firstOrFail();

        // 2. Récupérer TOUS les enfants pour le menu déroulant
        $siblings = $parent->children()->get();

        // 3. Récupérer la classe via l'inscription, sur l'année active de l'école DE CET ÉLÈVE
        // (un parent peut avoir des enfants dans des écoles différentes)
        $currentYear = SchoolYear::where('school_id', $student->school_id)->where('is_active', true)->first();
        $enrollment = Enrollment::where('student_id', $studentId)
            ->where('school_year_id', $currentYear?->id)
            ->with('schoolClass')
            ->first();
        $schoolClassName = $enrollment ? $enrollment->schoolClass->name : 'Classe non assignée';

        // 4. Filtres de dates (par défaut : 30 derniers jours)
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        // 5. Récupérer les présences filtrées par date
        $attendances = Attendance::where('student_id', $studentId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        // 6. Statistiques basées sur la période filtrée
        $totalDays = $attendances->count();
        $presentCount = $attendances->where('status', 'present')->count();
        $absentCount = $attendances->where('status', 'absent')->count();
        $lateCount = $attendances->where('status', 'late')->count();
        $excusedCount = $attendances->where('status', 'excused')->count();
        $attendanceRate = $totalDays > 0 ? round(($presentCount / $totalDays) * 100, 1) : 0;

        return view('parent.attendance.index', compact(
            'student', 'siblings', 'schoolClassName', 'attendances',
            'totalDays', 'presentCount', 'absentCount', 'lateCount', 'excusedCount', 'attendanceRate',
            'startDate', 'endDate' // Passés à la vue pour le formulaire
        ));
    }
}
