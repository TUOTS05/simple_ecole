<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Events\StudentMarkedAbsent;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceExport;
use Barryvdh\DomPDF\Facade\Pdf;



class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $teacher = auth()->user();
        $assignments = $teacher->teacherAssignments()->with('schoolClass')->get();
        $assignedClassIds = $assignments->pluck('school_class_id')->toArray();

        if (empty($assignedClassIds)) {
            return view('teacher.attendance.index', [
                'assignments' => $assignments,
                'summaries' => collect(),
                'studentAbsenceHours' => collect(), // ✅ Ajouté pour éviter les erreurs
                'selectedClassId' => null,
                'selectedPeriod' => 'all',
                'groupBy' => 'day',
                'startDate' => now()->startOfMonth()->format('Y-m-d'),
                'endDate' => now()->format('Y-m-d'),
                'attendanceHasPeriod' => true,
            ]);
        }

        $selectedClassId = $request->get('class_id', $assignedClassIds[0]);
        $selectedPeriod = $request->get('period', 'all');
        $groupBy = $request->get('group_by', 'day');
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $attendanceHasPeriod = true;

        // Fonctions MySQL (le projet n'utilise pas PostgreSQL) : TO_CHAR faisait planter la page
        // dès qu'un enseignant choisissait un regroupement autre que "Jour".
        $groupByClause = 'DATE(date)';
        if ($groupBy === 'week') $groupByClause = "DATE(DATE_SUB(date, INTERVAL WEEKDAY(date) DAY))";
        elseif ($groupBy === 'month') $groupByClause = "DATE(DATE_FORMAT(date, '%Y-%m-01'))";
        elseif ($groupBy === 'year') $groupByClause = "DATE(DATE_FORMAT(date, '%Y-01-01'))";

        // 1. Requête pour le tableau de bilan global
        $query = Attendance::query()
            ->select(
                DB::raw("$groupByClause as period_date"),
                'attendances.school_class_id',
                'attendances.period',
                DB::raw('school_classes.name as class_name'),
                DB::raw("MAX(CONCAT(users.first_name, ' ', users.last_name)) as teacher_name"),
                DB::raw('SUM(CASE WHEN status = \'present\' THEN 1 ELSE 0 END) as present'),
                DB::raw('SUM(CASE WHEN status = \'absent\' THEN 1 ELSE 0 END) as absent'),
                DB::raw('SUM(CASE WHEN status = \'late\' THEN 1 ELSE 0 END) as late'),
                DB::raw('SUM(CASE WHEN status = \'excused\' THEN 1 ELSE 0 END) as excused'),
                DB::raw('COUNT(*) as total')
            )
            ->join('school_classes', 'attendances.school_class_id', '=', 'school_classes.id')
            ->leftJoin('users', 'attendances.user_id', '=', 'users.id')
            ->whereIn('attendances.school_class_id', $assignedClassIds)
            ->whereBetween('attendances.date', [$startDate, $endDate])
            ->when($selectedClassId, fn($q) => $q->where('attendances.school_class_id', $selectedClassId))
            ->when($selectedPeriod !== 'all', fn($q) => $q->where('attendances.period', $selectedPeriod))
            ->groupBy('period_date', 'attendances.school_class_id', 'attendances.period', 'school_classes.name')
            ->orderBy('period_date', 'desc')
            ->orderBy('school_classes.name', 'asc');

        $summaries = $query->paginate(20);

        // ✅ 2. NOUVEAU : Calcul des heures d'absence par élève (Déplacé ici, dans index)
        $studentAbsenceHours = collect();
        if ($selectedClassId) {
            $studentAbsenceHours = DB::table('attendances')
                ->select(
                    'students.id as student_id',
                    'students.matricule',
                    'students.first_name',
                    'students.last_name',
                    DB::raw("SUM(CASE WHEN attendances.period = 'matin' THEN 4 WHEN attendances.period = 'apres_midi' THEN 3 ELSE 0 END) as total_hours")
                )
                ->join('students', 'attendances.student_id', '=', 'students.id')
                ->where('attendances.school_class_id', $selectedClassId)
                ->where('attendances.status', 'absent') // On ne compte que les absences
                ->whereBetween('attendances.date', [$startDate, $endDate]) // Respecte les filtres "Du" et "Au"
                ->groupBy('students.id', 'students.matricule', 'students.first_name', 'students.last_name')
                ->orderBy('total_hours', 'desc') // Trie par les plus grands absents en premier
                ->get();
        }

        return view('teacher.attendance.index', compact(
            'assignments',
            'selectedClassId',
            'selectedPeriod',
            'groupBy',
            'startDate',
            'endDate',
            'attendanceHasPeriod',
            'summaries',
            'studentAbsenceHours' // ✅ Passé à la vue
        ));
    }

    public function create(Request $request)
    {
        $teacher = auth()->user();
        $teacherClasses = SchoolClass::whereHas('teacherAssignments', fn($q) => $q->where('user_id', $teacher->id))->orderBy('name')->get();

        $selectedClassId = $request->get('class_id') ?? $request->route('classId') ?? ($teacherClasses->first()->id ?? null);
        $selectedDate = $request->get('date', Carbon::today()->format('Y-m-d'));
        $selectedPeriod = $request->get('period', 'matin');

        $class = null;
        $students = collect();
        $existingAttendances = collect();

        if ($selectedClassId) {
            if (!$teacher->teacherAssignments()->where('school_class_id', $selectedClassId)->first()) {
                abort(403, 'Accès non autorisé.');
            }
            $class = SchoolClass::with(['students' => fn($q) => $q->where('status', 'active')->orderBy('last_name')->orderBy('first_name')])->findOrFail($selectedClassId);
            $students = $class->students;
            $existingAttendances = Attendance::where('school_class_id', $selectedClassId)
                ->where('date', $selectedDate)
                ->where('period', $selectedPeriod)
                ->get()->keyBy('student_id');
        }

        return view('teacher.attendance.create', compact('teacherClasses', 'class', 'students', 'selectedClassId', 'selectedDate', 'selectedPeriod', 'existingAttendances'));
    }

    public function history($classId = null)
    {
        $teacher = auth()->user();
        $teacherClasses = SchoolClass::whereHas('teacherAssignments', fn($q) => $q->where('user_id', $teacher->id))->orderBy('name')->get();

        $classId = $classId ?? $teacherClasses->first()->id ?? null;

        if (!$classId || !$teacher->teacherAssignments()->where('school_class_id', $classId)->first()) {
            abort(403, 'Accès non autorisé.');
        }

        $class = SchoolClass::findOrFail($classId);

        $attendances = DB::table('attendances')
            ->select(
                'date',
                'period',
                DB::raw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent"),
                DB::raw("SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late"),
                DB::raw("SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused"),
                DB::raw('COUNT(*) as total')
            )
            ->where('school_class_id', $classId)
            ->groupBy('date', 'period')
            ->orderBy('date', 'desc')
            ->get()
            ->map(fn($row) => (array) $row);

        return view('teacher.attendance.history', compact('class', 'attendances'));
    }

    public function store(Request $request)
    {
        $teacher = auth()->user();

        $validated = $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'date' => 'required|date',
            'period' => 'required|in:matin,apres_midi,apres-midi',
            'attendances' => 'required|array',
            'attendances.*.status' => 'required|in:present,absent,late,excused',
            'attendances.*.notes' => 'nullable|string|max:255',
        ]);

        $periodNormalise = str_replace('-', '_', $validated['period']);

        if (!$teacher->teacherAssignments()->where('school_class_id', $validated['class_id'])->first()) {
            abort(403, 'Action non autorisée.');
        }

        DB::beginTransaction();
        try {
            $attendancesData = [];
            foreach ($validated['attendances'] as $studentId => $data) {
                $attendancesData[] = [
                    'school_id' => $teacher->school_id,
                    'school_class_id' => $validated['class_id'],
                    'student_id' => $studentId,
                    'user_id' => $teacher->id,
                    'date' => $validated['date'],
                    'period' => $periodNormalise,
                    'status' => $data['status'],
                    'notes' => $data['notes'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($attendancesData)) {
                Attendance::upsert(
                    $attendancesData,
                    ['school_class_id', 'student_id', 'date', 'period'],
                    ['status', 'notes', 'user_id', 'updated_at']
                );
            }

            DB::commit();

            foreach ($validated['attendances'] as $studentId => $data) {
                if ($data['status'] === 'absent') {
                    $attendance = Attendance::where('student_id', $studentId)
                        ->where('date', $validated['date'])
                        ->where('period', $periodNormalise)
                        ->first();
                    if ($attendance) event(new StudentMarkedAbsent($attendance));
                }
            }

            // ✅ La méthode store doit SEULEMENT rediriger après succès
            return redirect()->route('teacher.attendance.index', ['class_id' => $validated['class_id']])
                ->with('success', '✅ Appel enregistré avec succès !');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur : ' . $e->getMessage()])->withInput();
        }
    }


    public function exportAttendanceExcel(Request $request)
    {
        $teacher = auth()->user();
        $assignedClassIds = $teacher->teacherAssignments()->pluck('school_class_id')->toArray();

        $classId = $request->get('class_id');
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        return Excel::download(
            new AttendanceExport($classId, $startDate, $endDate, true, $assignedClassIds),
            'presences_' . date('Y-m-d') . '.xlsx'
        );
    }

    // public function exportAttendancePdf(Request $request)
    // {
    //     $teacher = auth()->user();
    //     $assignedClassIds = $teacher->teacherAssignments()->pluck('school_class_id')->toArray();

    //     $classId = $request->get('class_id');
    //     $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
    //     $endDate = $request->get('end_date', now()->format('Y-m-d'));

    //     // On réutilise la même logique de requête que pour l'Excel
    //     $query = \App\Models\Attendance::query()
    //         ->select(
    //             'attendances.date',
    //             'attendances.period',
    //             'school_classes.name as class_name',
    //             DB::raw("CONCAT(students.last_name, ' ', students.first_name) as student_name"),
    //             'attendances.status',
    //             'attendances.notes'
    //         )
    //         ->join('students', 'attendances.student_id', '=', 'students.id')
    //         ->join('school_classes', 'attendances.school_class_id', '=', 'school_classes.id')
    //         ->whereIn('attendances.school_class_id', $assignedClassIds)
    //         ->whereBetween('attendances.date', [$startDate, $endDate])
    //         ->orderBy('attendances.date', 'desc');

    //     if ($classId) $query->where('attendances.school_class_id', $classId);

    //     $attendances = $query->get();
    //     $className = $classId ? \App\Models\SchoolClass::find($classId)->name : 'Toutes mes classes';

    //     $pdf = Pdf::loadView('teacher.exports.attendance_pdf', compact('attendances', 'className', 'startDate', 'endDate'));
    //     return $pdf->download('presences_' . date('Y-m-d') . '.pdf');
    // }

    //     public function exportAttendancePdf(Request $request)
    // {
    //     $teacher = auth()->user();
    //     $assignedClassIds = $teacher->teacherAssignments()->pluck('school_class_id')->toArray();

    //     $classId = $request->get('class_id');
    //     $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
    //     $endDate = $request->get('end_date', now()->format('Y-m-d'));

    //     // ✅ 1. Récupérer le nom complet de l'enseignant connecté
    //     $teacherName = trim(($teacher->first_name ?? '') . ' ' . ($teacher->last_name ?? '')) ?: ($teacher->name ?? 'Enseignant non spécifié');

    //     // 2. Requête pour les données du tableau
    //     $query = \App\Models\Attendance::query()
    //         ->select(
    //             'attendances.date',
    //             'attendances.period',
    //             'school_classes.name as class_name',
    //             DB::raw("CONCAT(students.last_name, ' ', students.first_name) as student_name"),
    //             'attendances.status',
    //             'attendances.notes'
    //         )
    //         ->join('students', 'attendances.student_id', '=', 'students.id')
    //         ->join('school_classes', 'attendances.school_class_id', '=', 'school_classes.id')
    //         ->whereIn('attendances.school_class_id', $assignedClassIds)
    //         ->whereBetween('attendances.date', [$startDate, $endDate])
    //         ->orderBy('attendances.date', 'desc')
    //         ->orderBy('attendances.period', 'desc'); // Tri cohérent

    //     if ($classId) {
    //         $query->where('attendances.school_class_id', $classId);
    //     }

    //     $attendances = $query->get();
        
    //     $className = $classId ? \App\Models\SchoolClass::find($classId)->name : 'Toutes mes classes';
        
    //     // ✅ 3. Année scolaire (à adapter si vous avez une relation school_year sur la classe)
    //     $schoolYear = '2025-2026'; 

    //     // ✅ 4. Passage de toutes les variables à la vue, y compris teacherName
    //     $pdf = Pdf::loadView('teacher.exports.attendance_pdf', compact(
    //         'attendances', 
    //         'className', 
    //         'startDate', 
    //         'endDate',
    //         'teacherName',
    //         'schoolYear'
    //     ));
        
    //     return $pdf->download('rapport_presences_absences_' . date('Y-m-d') . '.pdf');
    // }

        public function exportAttendancePdf(Request $request)
    {
        $teacher = auth()->user();
        $assignedClassIds = $teacher->teacherAssignments()->pluck('school_class_id')->toArray();

        $classId = $request->get('class_id');
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $teacherName = trim(($teacher->first_name ?? '') . ' ' . ($teacher->last_name ?? '')) ?: ($teacher->name ?? 'Enseignant non spécifié');
        $schoolYear = '2025-2026'; // Adaptez si vous avez une relation school_year

        // ✅ 1. Récupération du chemin absolu du logo de l'école
        // Adaptez 'school.logo' selon votre modèle réel (ex: $teacher->school->logo)
        $schoolLogoPath = null;
        if (isset($teacher->school) && $teacher->school->logo) {
            // Si le logo est stocké dans storage/app/public
            $schoolLogoPath = public_path('storage/' . $teacher->school->logo);
            
            // OU si le logo est directement dans public/images/
            // $schoolLogoPath = public_path('images/' . $teacher->school->logo);
        }
        
        // Fallback vers un logo par défaut si aucun n'est trouvé
        if (!$schoolLogoPath || !file_exists($schoolLogoPath)) {
            $schoolLogoPath = public_path('images/default-logo.png'); // Créez ce fichier dans public/images/
        }

        $query = \App\Models\Attendance::query()
            ->select(
                'attendances.date',
                'attendances.period',
                'school_classes.name as class_name',
                DB::raw("CONCAT(students.last_name, ' ', students.first_name) as student_name"),
                'attendances.status',
                'attendances.notes'
            )
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->join('school_classes', 'attendances.school_class_id', '=', 'school_classes.id')
            ->whereIn('attendances.school_class_id', $assignedClassIds)
            ->whereBetween('attendances.date', [$startDate, $endDate])
            ->orderBy('attendances.date', 'desc')
            ->orderBy('attendances.period', 'desc');

        if ($classId) {
            $query->where('attendances.school_class_id', $classId);
        }

        $attendances = $query->get();
        $className = $classId ? \App\Models\SchoolClass::find($classId)->name : 'Toutes mes classes';

        $pdf = Pdf::loadView('teacher.exports.attendance_pdf', compact(
            'attendances', 
            'className', 
            'startDate', 
            'endDate',
            'teacherName',
            'schoolYear',
            'schoolLogoPath' // ✅ 2. Passage du chemin absolu à la vue
        ));
        
        // Optionnel : pour forcer le format A4 et l'orientation
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->download('rapport_presences_absences_' . date('Y-m-d') . '.pdf');
    }
}
