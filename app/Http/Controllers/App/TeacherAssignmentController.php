<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Http\Request;

class TeacherAssignmentController extends Controller
{
    /**
     * Affiche la liste des enseignants assignés aux classes pour l'année en cours.
     */
    public function index()
    {
        $schoolId = session('current_school_id');
        $currentYear = SchoolYear::where('school_id', $schoolId)
            ->where('is_active', true)
            ->first();

        if (! $currentYear) {
            return redirect()->route('app.dashboard')->with('error', 'Aucune année scolaire active.');
        }

        // Récupérer les assignations de l'année en cours avec les relations
        $assignments = TeacherAssignment::where('school_id', $schoolId)
            ->where('school_year_id', $currentYear->id)
            ->with(['schoolClass', 'teacher'])
            ->orderBy('is_main_teacher', 'desc') // Titulaires d'abord
            ->orderBy('school_class_id')
            ->get()
            ->groupBy('school_class_id'); // Grouper par classe pour l'affichage

        // Récupérer toutes les classes pour voir celles qui n'ont pas d'enseignant
        $classes = SchoolClass::where('school_id', $schoolId)
            ->orderBy('cycle')
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        return view('app.teacher-assignments.index', compact('assignments', 'classes', 'currentYear'));
    }

    /**
     * Affiche le formulaire pour assigner un enseignant.
     */
    public function create()
    {
        $schoolId = session('current_school_id');
        $currentYear = SchoolYear::where('school_id', $schoolId)->where('is_active', true)->first();

        // Récupérer les classes
        $classes = SchoolClass::where('school_id', $schoolId)->orderBy('name')->get();

        // Récupérer les enseignants de l'école (rôle 'teacher')
        // Note: On suppose que le rôle est stocké dans la colonne 'role'
        $teachers = User::where('school_id', $schoolId)
            ->where('role', 'teacher')
            ->orderBy('last_name')
            ->get();

        return view('app.teacher-assignments.create', compact('classes', 'teachers', 'currentYear'));
    }

    /**
     * Enregistre une nouvelle assignation.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'user_id' => 'required|exists:users,id',
            'is_main_teacher' => 'boolean',
        ]);

        $schoolId = session('current_school_id');
        $currentYear = SchoolYear::where('school_id', $schoolId)->where('is_active', true)->first();

        // Vérifier si l'enseignant est déjà assigné à cette classe pour cette année
        $exists = TeacherAssignment::where('school_class_id', $validated['school_class_id'])
            ->where('user_id', $validated['user_id'])
            ->where('school_year_id', $currentYear->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['error' => 'Cet enseignant est déjà assigné à cette classe.'])->withInput();
        }

        TeacherAssignment::create([
            'school_id' => $schoolId,
            'school_class_id' => $validated['school_class_id'],
            'user_id' => $validated['user_id'],
            'school_year_id' => $currentYear->id,
            'is_main_teacher' => $request->boolean('is_main_teacher', true),
        ]);

        return redirect()->route('app.teacher-assignments.index')
            ->with('success', 'Enseignant assigné avec succès !');
    }

    /**
     * Supprime une assignation (désassigner un enseignant).
     */
    public function destroy(TeacherAssignment $teacherAssignment)
    {
        // Sécurité : Vérifier que l'assignation appartient bien à l'école courante
        if ($teacherAssignment->school_id !== session('current_school_id')) {
            abort(403);
        }

        $teacherAssignment->delete();

        return redirect()->route('app.teacher-assignments.index')
            ->with('success', 'Enseignant retiré de la classe.');
    }
}
