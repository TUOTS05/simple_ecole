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
                $groupByRaw = 'DATE(DATE_SUB(attendances.date, INTERVAL WEEKDAY(attendances.date) DAY))';
                break;
            case 'month':
                $groupByRaw = "DATE(DATE_FORMAT(attendances.date, '%Y-%m-01'))";
                break;
            case 'year':
                $groupByRaw = "DATE(DATE_FORMAT(attendances.date, '%Y-01-01'))";
                break;
            default:
                $groupByRaw = 'DATE(attendances.date)';
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
