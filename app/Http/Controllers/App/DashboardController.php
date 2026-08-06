<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Enrollment;
use App\Models\StudentInstallment;
use App\Models\Payment;
use App\Models\SchoolYear;
use Illuminate\Support\Facades\DB;
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
        $totalTeachers = \App\Models\User::where('school_id', $schoolId)
                                         ->where('role', 'teacher')
                                         ->count();

        // 2. Répartition des élèves par classe (prêt pour un graphique ou une liste)
        $studentsPerClass = \App\Models\SchoolClass::where('school_id', $schoolId)
            ->withCount(['enrollments' => function ($q) { 
                $q->where('status', 'enrolled'); 
            }])
            ->get()
            ->map(fn($c) => ['name' => $c->name, 'count' => $c->enrollments_count]);


        // ==========================================
        // KPI EXISTANTS (Pour ne pas casser votre vue actuelle)
        // ==========================================
        
        $totalStudents = \App\Models\Student::where('school_id', $schoolId)->where('status', 'active')->count();
        $totalClasses = \App\Models\SchoolClass::where('school_id', $schoolId)->count();
        $totalEnrollments = \App\Models\Enrollment::where('school_id', $schoolId)->where('status', 'enrolled')->count();
        
        $enrollmentRate = $totalStudents > 0 ? round(($totalEnrollments / $totalStudents) * 100, 1) : 0;

        // Finances
        $totalTuitionExpected = \App\Models\StudentInstallment::where('school_id', $schoolId)->sum('amount');
        $totalTuitionPaid = \App\Models\StudentInstallment::where('school_id', $schoolId)->sum('paid_amount');
        $totalTuitionRemaining = max(0, $totalTuitionExpected - $totalTuitionPaid);
        $collectionRate = $totalTuitionExpected > 0 ? round(($totalTuitionPaid / $totalTuitionExpected) * 100, 1) : 0;
        
        $totalCollected = \App\Models\Payment::where('school_id', $schoolId)
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        $registrationPaidCount = \App\Models\Enrollment::where('school_id', $schoolId)->where('registration_fee_paid', true)->count();

        // Alertes
        $lateInstallments = \App\Models\StudentInstallment::where('school_id', $schoolId)
            ->where('status', 'pending')
            ->whereDate('due_date', '<', now())
            ->with('enrollment.student')
            ->limit(5)
            ->get();
            
        $recentAbsences = collect(); // Sécurité : évite le bug si la table attendance n'est pas encore remplie

        // Données pour les graphiques (Valeurs par défaut sécurisées pour éviter les erreurs JS)
        $paymentLabels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'];
        $paymentData = [0, 0, 0, 0, 0, 0]; 
        
        $paymentStatusCounts = [
            'paid' => \App\Models\StudentInstallment::where('school_id', $schoolId)->where('status', 'completed')->count(),
            'pending' => \App\Models\StudentInstallment::where('school_id', $schoolId)->where('status', 'pending')->count(),
            'partial' => \App\Models\StudentInstallment::where('school_id', $schoolId)->where('status', 'partial')->count(),
            'overdue' => $lateInstallments->count()
        ];

        $attendanceLabels = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        $attendancePresent = [0, 0, 0, 0, 0, 0, 0];
        $attendanceAbsent = [0, 0, 0, 0, 0, 0, 0];
        $attendanceLate = [0, 0, 0, 0, 0, 0, 0];
        $attendanceRate = 0;
        $presentCount = 0;

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