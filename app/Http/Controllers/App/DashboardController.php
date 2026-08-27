<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\StudentInstallment;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // public function index()
    // {
    //     $schoolId = session('current_school_id');
    //     $activeYear = SchoolYear::where('school_id', $schoolId)->where('is_active', true)->first();
    //     $yearId = $activeYear ? $activeYear->id : null;

    //     // 1. KPIs de base
    //     $totalStudents = Student::where('school_id', $schoolId)->where('status', 'active')->count();
    //     $totalClasses = SchoolClass::where('school_id', $schoolId)->count();
    //     $totalEnrollments = Enrollment::where('school_id', $schoolId)->where('school_year_id', $yearId)->count();

    //     $enrollmentRate = $totalClasses > 0 ? round(($totalEnrollments / ($totalClasses * 30)) * 100, 1) : 0; // Estimation basée sur 30 élèves/classe

    //     // 2. Statistiques Financières (Basées sur StudentInstallment)
    //     $financials = StudentInstallment::selectRaw('
    //         SUM(amount) as expected,
    //         SUM(paid_amount) as paid,
    //         SUM(amount - paid_amount) as remaining
    //     ')->where('school_id', $schoolId)->first();

    //     $totalTuitionExpected = $financials->expected ?? 0;
    //     $totalTuitionPaid = $financials->paid ?? 0;
    //     $totalTuitionRemaining = $financials->remaining ?? 0;

    //     $collectionRate = $totalTuitionExpected > 0 ? round(($totalTuitionPaid / $totalTuitionExpected) * 100, 1) : 0;

    //     // Frais d'inscription payés
    //     $registrationPaidCount = StudentInstallment::where('school_id', $schoolId)
    //         ->where('type', 'registration')
    //         ->where('status', 'paid')
    //         ->count();

    //     // 3. Statuts des paiements (Pour le graphique Doughnut)
    //     $paymentStatusCounts = [
    //         'paid' => StudentInstallment::where('school_id', $schoolId)->where('status', 'paid')->count(),
    //         'pending' => StudentInstallment::where('school_id', $schoolId)->where('status', 'pending')->count(),
    //         'partial' => StudentInstallment::where('school_id', $schoolId)->where('status', 'partial')->count(),
    //         'overdue' => StudentInstallment::where('school_id', $schoolId)->where('status', 'overdue')->count(),
    //     ];

    //     // 4. Graphique : Paiements par mois (6 derniers mois) - Compatible PostgreSQL
    //     $sixMonthsAgo = Carbon::now()->subMonths(6);
    //     $monthlyPayments = Payment::selectRaw("TO_CHAR(payment_date, 'YYYY-MM') as month, SUM(amount) as total")
    //         ->where('school_id', $schoolId)
    //         ->where('payment_date', '>=', $sixMonthsAgo)
    //         ->groupBy('month')
    //         ->orderBy('month', 'asc')
    //         ->get();

    //     $paymentLabels = [];
    //     $paymentData = [];
    //     foreach ($monthlyPayments as $mp) {
    //         // Convertit 'YYYY-MM' en objet Carbon pour un formatage joli (ex: "Juil. 2024")
    //         $paymentLabels[] = Carbon::createFromFormat('Y-m', $mp->month)->isoFormat('MMM YYYY');
    //         $paymentData[] = (float) $mp->total;
    //     }

    //     // 5. Taux de présence (Simulation ou requête réelle si vous avez la table attendances)
    //     $attendanceRate = 92;
    //     $presentCount = (int)($totalStudents * 0.92);
    //     $attendanceLabels = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven'];
    //     $attendancePresent = [45, 47, 46, 44, 48];
    //     $attendanceAbsent = [3, 2, 4, 5, 2];
    //     $attendanceLate = [2, 1, 0, 1, 0];

    //     // 6. Alertes : Paiements en retard (Échéances dépassées et non payées)
    //     $lateInstallments = StudentInstallment::where('school_id', $schoolId)
    //         ->where('status', '!=', 'paid')
    //         ->where('due_date', '<', Carbon::today())
    //         ->with('enrollment.student')
    //         ->orderBy('due_date', 'asc')
    //         ->limit(5)
    //         ->get();

    //     // Alertes : Absences récentes (À adapter selon votre logique d'absence)
    //     $recentAbsences = collect();

    //     return view('app.dashboard', compact(
    //         'totalStudents', 'totalClasses', 'enrollmentRate', 'totalEnrollments',
    //         'collectionRate', 'totalTuitionExpected', 'totalTuitionPaid', 'totalTuitionRemaining',
    //         'registrationPaidCount', 'paymentStatusCounts',
    //         'paymentLabels', 'paymentData',
    //         'attendanceRate', 'presentCount', 'attendanceLabels', 'attendancePresent', 'attendanceAbsent', 'attendanceLate',
    //         'lateInstallments', 'recentAbsences'
    //     ));
    // }

    public function index()
    {
        $schoolId = session('current_school_id') ?? auth()->user()->school_id;

        // ==========================================
        // NOUVEAUX KPI (Demandés)
        // ==========================================

        // 1. Nombre d'enseignants
        $totalTeachers = User::where('school_id', $schoolId)
            ->where('role', 'teacher')
            ->count();

        // 2. Répartition des élèves par classe (prêt pour un graphique ou une liste)
        $studentsPerClass = SchoolClass::where('school_id', $schoolId)
            ->withCount(['enrollments' => function ($q) {
                $q->where('status', 'enrolled');
            }])
            ->get()
            ->map(fn ($c) => ['name' => $c->name, 'count' => $c->enrollments_count]);

        // ==========================================
        // KPI EXISTANTS (Pour ne pas casser votre vue actuelle)
        // ==========================================

        $totalStudents = Student::where('school_id', $schoolId)->where('status', 'active')->count();
        $totalClasses = SchoolClass::where('school_id', $schoolId)->count();
        $totalEnrollments = Enrollment::where('school_id', $schoolId)->where('status', 'enrolled')->count();

        $enrollmentRate = $totalStudents > 0 ? round(($totalEnrollments / $totalStudents) * 100, 1) : 0;

        // Finances
        $totalTuitionExpected = StudentInstallment::where('school_id', $schoolId)->sum('amount');
        $totalTuitionPaid = StudentInstallment::where('school_id', $schoolId)->sum('paid_amount');
        $totalTuitionRemaining = max(0, $totalTuitionExpected - $totalTuitionPaid);
        $collectionRate = $totalTuitionExpected > 0 ? round(($totalTuitionPaid / $totalTuitionExpected) * 100, 1) : 0;

        $totalCollected = Payment::where('school_id', $schoolId)
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        $registrationPaidCount = Enrollment::where('school_id', $schoolId)->where('registration_fee_paid', true)->count();

        // Alertes : toute échéance non soldée (pending/partial/overdue) et déjà échue
        $lateInstallments = StudentInstallment::where('school_id', $schoolId)
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '<', now())
            ->with('enrollment.student')
            ->limit(5)
            ->get();

        // Élèves marqués absents au moins une fois durant les 7 derniers jours
        $recentAbsences = Student::where('school_id', $schoolId)
            ->whereHas('attendances', function ($q) {
                $q->where('status', 'absent')->where('date', '>=', now()->subDays(7));
            })
            ->orderBy('last_name')
            ->limit(5)
            ->get();

        // Paiements encaissés sur les 6 derniers mois (pour le graphique en courbe)
        $paymentsByMonth = Payment::where('school_id', $schoolId)
            ->where('payment_date', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as ym, SUM(amount) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $paymentLabels = [];
        $paymentData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $paymentLabels[] = $month->translatedFormat('M Y');
            $paymentData[] = (float) ($paymentsByMonth[$month->format('Y-m')] ?? 0);
        }

        $paymentStatusCounts = [
            'paid' => StudentInstallment::where('school_id', $schoolId)->where('status', 'paid')->count(),
            'pending' => StudentInstallment::where('school_id', $schoolId)->where('status', 'pending')->count(),
            'partial' => StudentInstallment::where('school_id', $schoolId)->where('status', 'partial')->count(),
            'overdue' => StudentInstallment::where('school_id', $schoolId)->where('status', 'overdue')->count(),
        ];

        // Présences des 7 derniers jours (pour le graphique + le taux affiché en KPI)
        $attendanceByDay = Attendance::where('school_id', $schoolId)
            ->where('date', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('date, status, count(*) as count')
            ->groupBy('date', 'status')
            ->get()
            ->groupBy(fn ($row) => $row->date->format('Y-m-d'));

        $attendanceLabels = [];
        $attendancePresent = [];
        $attendanceAbsent = [];
        $attendanceLate = [];
        $totalPresent = 0;
        $totalMarked = 0;

        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $rows = $attendanceByDay->get($day->format('Y-m-d'), collect());
            $present = (int) $rows->firstWhere('status', 'present')?->count;
            $absent = (int) $rows->firstWhere('status', 'absent')?->count;
            $late = (int) $rows->firstWhere('status', 'late')?->count;

            $attendanceLabels[] = $day->translatedFormat('D');
            $attendancePresent[] = $present;
            $attendanceAbsent[] = $absent;
            $attendanceLate[] = $late;

            $totalPresent += $present;
            $totalMarked += (int) $rows->sum('count');
        }

        $attendanceRate = $totalMarked > 0 ? round(($totalPresent / $totalMarked) * 100, 1) : 0;
        $presentCount = Attendance::where('school_id', $schoolId)
            ->whereDate('date', now()->toDateString())
            ->where('status', 'present')
            ->count();

        // Envoi de TOUTES les variables à la vue
        return view('app.dashboard', compact(
            'totalTeachers', 'studentsPerClass', // NOUVEAUX
            'totalStudents', 'totalClasses', 'totalEnrollments', 'enrollmentRate',
            'totalTuitionExpected', 'totalTuitionPaid', 'totalTuitionRemaining', 'collectionRate', 'totalCollected', 'registrationPaidCount',
            'lateInstallments', 'recentAbsences',
            'paymentLabels', 'paymentData', 'paymentStatusCounts',
            'attendanceLabels', 'attendancePresent', 'attendanceAbsent', 'attendanceLate', 'attendanceRate', 'presentCount'
        ));
    }
}
