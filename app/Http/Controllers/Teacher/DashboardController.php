<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeacherAssignment;
use App\Models\SchoolYear;
use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Tableau de bord principal de l'enseignant
     */
    public function index()
    {
        $teacherId = auth()->id();
        $schoolId = session('current_school_id');
        $currentYear = SchoolYear::where('school_id', $schoolId)
            ->where('is_active', true)
            ->first();

        // Récupérer les classes assignées à cet enseignant pour l'année en cours
        $assignments = TeacherAssignment::where('user_id', $teacherId)
            ->where('school_year_id', $currentYear->id)
            ->with(['schoolClass' => function($query) {
                $query->withCount(['students' => function($q) {
                    $q->where('status', 'active');
                }]);
            }])
            ->get();

        // Statistiques rapides
        $totalStudents = $assignments->sum(function($assignment) {
            return $assignment->schoolClass->students_count;
        });

        $today = now()->toDateString();
        $todayAttendances = Attendance::where('school_id', $schoolId)
            ->where('date', $today)
            ->count();

        return view('teacher.dashboard', compact('assignments', 'totalStudents', 'todayAttendances', 'currentYear'));
    }

    /**
     * Liste des classes assignées
     */
    public function classes()
    {
        $teacherId = auth()->id();
        $schoolId = session('current_school_id');
        $currentYear = SchoolYear::where('school_id', $schoolId)
            ->where('is_active', true)
            ->first();

        $assignments = TeacherAssignment::where('user_id', $teacherId)
            ->where('school_year_id', $currentYear->id)
            ->with(['schoolClass' => function($query) {
                $query->withCount(['students' => function($q) {
                    $q->where('status', 'active');
                }]);
            }])
            ->get();

        return view('teacher.classes', compact('assignments', 'currentYear'));
    }

    /**
     * Détails d'une classe (liste des élèves)
     */
    public function classDetails($classId)
    {
        $teacherId = auth()->id();
        $schoolId = session('current_school_id');
        $currentYear = SchoolYear::where('school_id', $schoolId)
            ->where('is_active', true)
            ->first();

        // Vérifier que l'enseignant est bien assigné à cette classe
        $assignment = TeacherAssignment::where('user_id', $teacherId)
            ->where('school_class_id', $classId)
            ->where('school_year_id', $currentYear->id)
            ->firstOrFail();

        $class = $assignment->schoolClass;
        $students = $class->students()
            ->where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('teacher.class-details', compact('class', 'students', 'assignment'));
    }
}