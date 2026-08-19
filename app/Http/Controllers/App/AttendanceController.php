<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AttendanceController extends Controller
{

        public function index(Request $request)
    {
        $schoolId = session('current_school_id');
        $classes = SchoolClass::where('school_id', $schoolId)->orderBy('cycle')->orderBy('name')->get();

        $selectedClassId = $request->get('class_id');
        $selectedPeriod = $request->get('period', 'all');
        $groupBy = $request->get('group_by', 'day');
        $startDate = $request->get('start_date', Carbon::now()->subMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $attendanceHasPeriod = Schema::hasColumn('attendances', 'period');

        $query = Attendance::query()
            ->join('school_classes', 'attendances.school_class_id', '=', 'school_classes.id')
            ->where('attendances.school_id', $schoolId)
            ->whereBetween('attendances.date', [$startDate, $endDate]);

        if ($selectedClassId) {
            $query->where('attendances.school_class_id', $selectedClassId);
        }

        if ($attendanceHasPeriod && $selectedPeriod !== 'all') {
            $query->where('attendances.period', $selectedPeriod);
        }

        switch ($groupBy) {
            case 'week':
                $groupByRaw = "DATE(DATE_SUB(attendances.date, INTERVAL WEEKDAY(attendances.date) DAY))";
                break;
            case 'month':
                $groupByRaw = "DATE(DATE_FORMAT(attendances.date, '%Y-%m-01'))";
                break;
            case 'year':
                $groupByRaw = "DATE(DATE_FORMAT(attendances.date, '%Y-01-01'))";
                break;
            default:
                $groupByRaw = "DATE(attendances.date)";
                $groupBy = 'day';
                break;
        }

        $summaryQuery = $query->select([
            'attendances.school_class_id',
            'school_classes.name as class_name',
            DB::raw("{$groupByRaw} as period_date"),
            DB::raw("sum(case when attendances.status = 'present' then 1 else 0 end) as present"),
            DB::raw("sum(case when attendances.status = 'absent' then 1 else 0 end) as absent"),
            DB::raw("sum(case when attendances.status = 'late' then 1 else 0 end) as late"),
            DB::raw("sum(case when attendances.status = 'excused' then 1 else 0 end) as excused"),
            DB::raw('count(*) as total'),
        ]);

        if ($attendanceHasPeriod) {
            $summaryQuery->addSelect('attendances.period');
        }

        $summaryQuery->groupBy('attendances.school_class_id');
        $summaryQuery->groupBy('school_classes.name');
        $summaryQuery->groupByRaw($groupByRaw);

        if ($attendanceHasPeriod) {
            $summaryQuery->groupBy('attendances.period');
        }

        $summaries = $summaryQuery->orderBy('period_date', 'desc')
            ->orderBy('school_class_id')
            ->paginate(15)
            ->appends($request->query());

        return view('app.attendances.index', compact(
            'classes',
            'summaries',
            'selectedClassId',
            'selectedPeriod',
            'groupBy',
            'startDate',
            'endDate',
            'attendanceHasPeriod'
        ));
    }

    public function create(Request $request)
    {
        $schoolId = session('current_school_id');
        $classes = SchoolClass::where('school_id', $schoolId)->orderBy('cycle')->orderBy('name')->get();
        
        $selectedClassId = $request->get('class_id');
        $selectedDate = $request->get('date', now()->format('Y-m-d'));
        $selectedPeriod = $request->get('period', 'matin');
        $attendanceHasPeriod = Schema::hasColumn('attendances', 'period');
        
        $students = collect();
        $existingAttendances = collect();

        if ($selectedClassId && $selectedDate) {
            $class = SchoolClass::find($selectedClassId);
            $students = $class->students()->orderBy('last_name')->get();
            
            // Récupérer les présences existantes pour cette date (pour pré-cocher)
            $query = Attendance::where('school_id', $schoolId)
                ->where('date', $selectedDate)
                ->whereIn('student_id', $students->pluck('id'));

            if ($attendanceHasPeriod) {
                $query->where('period', $selectedPeriod);
            }

            $existingAttendances = $query->get()->keyBy('student_id');
        }

        return view('app.attendances.create', compact('classes', 'students', 'selectedClassId', 'selectedDate', 'selectedPeriod', 'existingAttendances'));
    }

    public function store(Request $request)
    {
        $attendanceHasPeriod = Schema::hasColumn('attendances', 'period');

        $rules = [
            'class_id' => 'required|exists:school_classes,id',
            'date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.status' => 'required|in:present,absent,late,excused',
            'attendances.*.notes' => 'nullable|string|max:255',
        ];

        if ($attendanceHasPeriod) {
            $rules['period'] = 'required|in:matin,apres_midi';
        }

        $validated = $request->validate($rules);

        $schoolId = session('current_school_id');
        $date = $validated['date'];
        $classId = $validated['class_id'];
        $period = $attendanceHasPeriod ? $validated['period'] : null;

        $class = SchoolClass::find($classId);
        $studentIds = $class->students->pluck('id');

        // Supprimer les anciennes présences pour cette classe et cette date (pour éviter les doublons si on modifie)
        Attendance::where('school_id', $schoolId)
            ->where('date', $date)
            ->whereIn('student_id', $studentIds)
            ->delete();

        // Insérer les nouvelles présences
        $records = [];
        foreach ($validated['attendances'] as $studentId => $data) {
            $record = [
                'school_id' => $schoolId,
                'student_id' => $studentId,
                'school_class_id' => $classId,
                'date' => $date,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
                'marked_by' => auth()->id(),
                'notified_parent' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($attendanceHasPeriod) {
                $record['period'] = $period;
            }

            $records[] = $record;
        }

        if (!empty($records)) {
            Attendance::insert($records);
        }

        return redirect()->route('app.attendances.index')
            ->with('success', 'Appel enregistré avec succès pour le ' . Carbon::parse($date)->format('d/m/Y') . ' !');
    }

    public function show(Request $request)
    {
        $schoolId = session('current_school_id');
        $date = $request->get('date', now()->format('Y-m-d'));

        $query = Attendance::where('school_id', $schoolId)
            ->where('date', $date)
            ->with(['student.classes', 'markedBy'])
            ->orderBy('student_id');

        if (Schema::hasColumn('attendances', 'period')) {
            $query->orderBy('period');
        }

        $attendances = $query->get()->groupBy('student_id');

        return view('app.attendances.show', compact('attendances', 'date'));
    }

    // Les méthodes edit, update, destroy ne sont pas utilisées pour l'appel groupé
    public function edit(string $id) {}
    public function update(Request $request, string $id) {}
    public function destroy(string $id) {}


        public function showByDate(string $date)
    {
        $schoolId = session('current_school_id');

        $query = Attendance::where('school_id', $schoolId)
            ->where('date', $date)
            ->with(['student.classes', 'markedBy'])
            ->orderBy('status', 'desc');

        if (Schema::hasColumn('attendances', 'period')) {
            $query->orderBy('period');
        }

        $attendances = $query->get()->groupBy('student_id');

        return view('app.attendances.show', compact('attendances', 'date'));
    }
}