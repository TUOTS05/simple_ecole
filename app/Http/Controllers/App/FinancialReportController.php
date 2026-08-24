<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialReportController extends Controller
{
    /**
     * Impayés par classe (vue d'ensemble)
     */
    public function unpaidByClass(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('school_id', $schoolId)->where('is_active', true)->value('id'));

        // Requête d'agrégation basée sur student_installments
        $classes = DB::table('school_classes')
            ->select(
                'school_classes.id as class_id',
                'school_classes.name as class_name',
                DB::raw('COUNT(DISTINCT students.id) as total_students'),
                DB::raw('COALESCE(SUM(student_installments.amount), 0) as total_expected'),
                DB::raw('COALESCE(SUM(student_installments.paid_amount), 0) as total_paid'),
                DB::raw('COALESCE(SUM(student_installments.amount), 0) - COALESCE(SUM(student_installments.paid_amount), 0) as total_unpaid')
            )
            ->join('enrollments', 'school_classes.id', '=', 'enrollments.school_class_id')
            ->join('students', 'enrollments.student_id', '=', 'students.id')
            ->leftJoin('student_installments', 'enrollments.id', '=', 'student_installments.enrollment_id')
            ->where('enrollments.school_id', $schoolId)
            ->where('enrollments.school_year_id', $schoolYearId)
            ->groupBy('school_classes.id', 'school_classes.name')
            ->orderBy('school_classes.name')
            ->get()
            ->map(function ($class) {
                $class->recovery_rate = $class->total_expected > 0 
                    ? round(($class->total_paid / $class->total_expected) * 100, 1) 
                    : 0;
                return $class;
            });

        // Statistiques globales
        $globalStats = (object) [
            'total_expected' => $classes->sum('total_expected'),
            'total_paid' => $classes->sum('total_paid'),
            'total_unpaid' => $classes->sum('total_unpaid'),
            'recovery_rate' => $classes->sum('total_expected') > 0 
                ? round(($classes->sum('total_paid') / $classes->sum('total_expected')) * 100, 1) 
                : 0,
        ];

        $schoolYears = SchoolYear::where('school_id', $schoolId)->orderBy('start_date', 'desc')->get();

        return view('app.financial.unpaid_by_class', compact('classes', 'globalStats', 'schoolYears', 'schoolYearId'));
    }

    /**
     * Détail des impayés pour une classe spécifique
     */
    public function classDetail(Request $request, $classId)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('school_id', $schoolId)->where('is_active', true)->value('id'));

        $class = SchoolClass::findOrFail($classId);
        abort_unless($request->user()->can('view', $class), 404);

        // Calcul des totaux par élève à partir des échéances (installments)
        $students = DB::table('students')
            ->select(
                'students.id as student_id',
                'students.matricule',
                'students.first_name',
                'students.last_name',
                DB::raw('COALESCE(SUM(student_installments.amount), 0) as total_du'),
                DB::raw('COALESCE(SUM(student_installments.paid_amount), 0) as total_paye'),
                DB::raw('COALESCE(SUM(student_installments.amount), 0) - COALESCE(SUM(student_installments.paid_amount), 0) as total_reste')
            )
            ->join('enrollments', 'students.id', '=', 'enrollments.student_id')
            ->leftJoin('student_installments', 'enrollments.id', '=', 'student_installments.enrollment_id')
            ->where('enrollments.school_id', $schoolId)
            ->where('enrollments.school_class_id', $classId)
            ->where('enrollments.school_year_id', $schoolYearId)
            ->groupBy('students.id', 'students.matricule', 'students.first_name', 'students.last_name')
            ->orderBy('students.last_name')
            ->orderBy('students.first_name')
            ->get()
            ->map(function ($student) {
                $total = (float) $student->total_du;
                $paye = (float) $student->total_paye;
                
                $student->payment_rate = $total > 0 
                    ? round(($paye / $total) * 100, 1) 
                    : 0;
                    
                return $student;
            });

        $schoolYears = SchoolYear::where('school_id', $schoolId)->orderBy('start_date', 'desc')->get();

        return view('app.financial.class_detail', compact('class', 'students', 'schoolYears', 'schoolYearId'));
    }


        /**
     * Export Excel des impayés par classe
     */
    public function exportUnpaidByClassExcel(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('school_id', $schoolId)->where('is_active', true)->value('id'));

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\UnpaidByClassExport($schoolId, $schoolYearId),
            'impayes_par_classe_' . date('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Export PDF des impayés par classe
     */
    public function exportUnpaidByClassPdf(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', \App\Models\SchoolYear::where('school_id', $schoolId)->where('is_active', true)->value('id'));
        $schoolYear = \App\Models\SchoolYear::find($schoolYearId);
        $user = auth()->user();

        // 1. Réutiliser la même logique d'agrégation
        $classes = DB::table('school_classes')
            ->select(
                'school_classes.id as class_id',
                'school_classes.name as class_name',
                DB::raw('COUNT(DISTINCT students.id) as total_students'),
                DB::raw('COALESCE(SUM(student_installments.amount), 0) as total_expected'),
                DB::raw('COALESCE(SUM(student_installments.paid_amount), 0) as total_paid'),
                DB::raw('COALESCE(SUM(student_installments.amount), 0) - COALESCE(SUM(student_installments.paid_amount), 0) as total_unpaid')
            )
            ->join('enrollments', 'school_classes.id', '=', 'enrollments.school_class_id')
            ->join('students', 'enrollments.student_id', '=', 'students.id')
            ->leftJoin('student_installments', 'enrollments.id', '=', 'student_installments.enrollment_id')
            ->where('enrollments.school_id', $schoolId)
            ->where('enrollments.school_year_id', $schoolYearId)
            ->groupBy('school_classes.id', 'school_classes.name')
            ->orderBy('school_classes.name')
            ->get()
            ->map(function ($class) {
                $class->recovery_rate = $class->total_expected > 0 
                    ? round(($class->total_paid / $class->total_expected) * 100, 1) 
                    : 0;
                return $class;
            });

        // 2. Statistiques globales
        $globalStats = (object) [
            'total_expected' => $classes->sum('total_expected'),
            'total_paid' => $classes->sum('total_paid'),
            'total_unpaid' => $classes->sum('total_unpaid'),
            'recovery_rate' => $classes->sum('total_expected') > 0 
                ? round(($classes->sum('total_paid') / $classes->sum('total_expected')) * 100, 1) 
                : 0,
        ];

        // 3. Nom de l'utilisateur connecté
        $userName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'Non spécifié');

        // 4. Chemin absolu du logo de l'école (Indispensable pour DomPDF)
        $schoolLogoPath = null;
        if (isset($user->school) && $user->school->logo) {
            $schoolLogoPath = public_path('storage/' . $user->school->logo);
        }
        if (!$schoolLogoPath || !file_exists($schoolLogoPath)) {
            $schoolLogoPath = public_path('images/default-logo.png'); // Fallback
        }

        // 5. Génération du PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('app.financial.exports.unpaid_by_class_pdf', compact(
            'classes', 
            'globalStats', 
            'schoolYear', 
            'userName', 
            'schoolLogoPath'
        ));
        
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('etat_impayes_par_classe_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export Excel du détail par classe
     */
    public function exportClassDetailExcel(Request $request, $classId)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('school_id', $schoolId)->where('is_active', true)->value('id'));

        // Vérifie que la classe appartient bien à l'école courante avant tout export.
        $class = SchoolClass::findOrFail($classId);
        abort_unless($request->user()->can('view', $class), 404);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ClassDetailExport($classId, $schoolYearId, $schoolId),
            'detail_classe_' . $classId . '_' . date('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Export PDF du détail par classe
     */
    public function exportClassDetailPdf(Request $request, $classId)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', \App\Models\SchoolYear::where('school_id', $schoolId)->where('is_active', true)->value('id'));
        $schoolYear = \App\Models\SchoolYear::find($schoolYearId);
        $class = \App\Models\SchoolClass::findOrFail($classId);
        abort_unless($request->user()->can('view', $class), 404);
        $user = auth()->user();

        $students = DB::table('students')
            ->select(
                'students.id as student_id',
                'students.matricule',
                'students.first_name',
                'students.last_name',
                DB::raw('COALESCE(SUM(student_installments.amount), 0) as total_du'),
                DB::raw('COALESCE(SUM(student_installments.paid_amount), 0) as total_paye'),
                DB::raw('COALESCE(SUM(student_installments.amount), 0) - COALESCE(SUM(student_installments.paid_amount), 0) as total_reste')
            )
            ->join('enrollments', 'students.id', '=', 'enrollments.student_id')
            ->leftJoin('student_installments', 'enrollments.id', '=', 'student_installments.enrollment_id')
            ->where('enrollments.school_id', $schoolId)
            ->where('enrollments.school_class_id', $classId)
            ->where('enrollments.school_year_id', $schoolYearId)
            ->groupBy('students.id', 'students.matricule', 'students.first_name', 'students.last_name')
            ->orderBy('students.last_name')
            ->orderBy('students.first_name')
            ->get()
            ->map(function ($student) {
                $total = (float) $student->total_du;
                $paye = (float) $student->total_paye;
                $student->payment_rate = $total > 0 ? round(($paye / $total) * 100, 1) : 0;
                return $student;
            });

        // ✅ NOUVEAU : Calcul des statistiques globales de la classe
        $totalDu = $students->sum('total_du');
        $totalPaye = $students->sum('total_paye');
        $totalReste = $students->sum('total_reste');
        $recoveryRate = $totalDu > 0 ? round(($totalPaye / $totalDu) * 100, 1) : 0;

        $classStats = (object) [
            'total_du' => $totalDu,
            'total_paye' => $totalPaye,
            'total_reste' => $totalReste,
            'recovery_rate' => $recoveryRate,
        ];

        // Variables pour l'en-tête
        $userName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'Non spécifié');
        
        $schoolLogoPath = null;
        if (isset($user->school) && $user->school->logo) {
            $schoolLogoPath = public_path('storage/' . $user->school->logo);
        }
        if (!$schoolLogoPath || !file_exists($schoolLogoPath)) {
            $schoolLogoPath = public_path('images/default-logo.png');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('app.financial.exports.class_detail_pdf', compact(
            'class', 
            'students', 
            'schoolYear', 
            'userName', 
            'schoolLogoPath',
            'classStats' // ✅ Nouvelle variable passée à la vue
        ));
        
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('detail_classe_' . $classId . '_' . date('Y-m-d') . '.pdf');
    }


        /**
     * Détail financier d'un élève spécifique
     */
    public function studentDetail(Request $request, $studentId)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('school_id', $schoolId)->where('is_active', true)->value('id'));

        $student = \App\Models\Student::findOrFail($studentId);
        abort_unless($request->user()->can('view', $student), 404);

        // Récupérer l'inscription de l'élève
        $enrollment = \App\Models\Enrollment::where('student_id', $studentId)
            ->where('school_year_id', $schoolYearId)
            ->with('schoolClass')
            ->first();

        if (!$enrollment) {
            return back()->with('error', 'Aucune inscription trouvée pour cet élève cette année.');
        }

        // Récupérer les échéances (installments)
        $installments = \App\Models\StudentInstallment::where('enrollment_id', $enrollment->id)
            ->orderBy('due_date')
            ->get();

        // Récupérer l'historique des paiements
        $payments = \App\Models\Payment::where('enrollment_id', $enrollment->id)
            ->orderBy('payment_date', 'desc')
            ->get();

        // Calcul des totaux
        $totalDue = $installments->sum('amount');
        $totalPaid = $installments->sum('paid_amount');
        $totalRemaining = $totalDue - $totalPaid;
        $paymentRate = $totalDue > 0 ? round(($totalPaid / $totalDue) * 100, 1) : 0;

        $schoolYears = SchoolYear::where('school_id', $schoolId)->orderBy('start_date', 'desc')->get();

        return view('app.financial.student_detail', compact(
            'student', 'enrollment', 'installments', 'payments',
            'totalDue', 'totalPaid', 'totalRemaining', 'paymentRate',
            'schoolYears', 'schoolYearId'
        ));
    }

    /**
     * Export Excel du détail d'un élève
     */
    public function exportStudentDetailExcel(Request $request, $studentId)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('school_id', $schoolId)->where('is_active', true)->value('id'));

        // Vérifie que l'élève appartient bien à l'école courante avant tout export.
        $student = \App\Models\Student::findOrFail($studentId);
        abort_unless($request->user()->can('view', $student), 404);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\StudentDetailExport($studentId, $schoolYearId),
            'detail_eleve_' . $studentId . '_' . date('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Export PDF du détail d'un élève
     */
    public function exportStudentDetailPdf(Request $request, $studentId)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', \App\Models\SchoolYear::where('school_id', $schoolId)->where('is_active', true)->value('id'));
        $schoolYear = \App\Models\SchoolYear::find($schoolYearId);
        $user = auth()->user();

        $student = \App\Models\Student::findOrFail($studentId);
        abort_unless($request->user()->can('view', $student), 404);

        $enrollment = \App\Models\Enrollment::where('student_id', $studentId)
            ->where('school_year_id', $schoolYearId)
            ->with('schoolClass')
            ->first();

        if (!$enrollment) {
            abort(404, 'Aucune inscription trouvée.');
        }

        $installments = \App\Models\StudentInstallment::where('enrollment_id', $enrollment->id)
            ->orderBy('due_date')
            ->get();

        $payments = \App\Models\Payment::where('enrollment_id', $enrollment->id)
            ->orderBy('payment_date', 'desc')
            ->get();

        $totalDue = $installments->sum('amount');
        $totalPaid = $installments->sum('paid_amount');
        $totalRemaining = $totalDue - $totalPaid;
        $paymentRate = $totalDue > 0 ? round(($totalPaid / $totalDue) * 100, 1) : 0;

        // ✅ Ajout des variables pour l'en-tête uniforme
        $userName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'Non spécifié');
        
        $schoolLogoPath = null;
        if (isset($user->school) && $user->school->logo) {
            $schoolLogoPath = public_path('storage/' . $user->school->logo);
        }
        if (!$schoolLogoPath || !file_exists($schoolLogoPath)) {
            $schoolLogoPath = public_path('images/default-logo.png'); // Fallback
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('app.financial.exports.student_detail_pdf', compact(
            'student', 
            'enrollment', 
            'installments', 
            'payments',
            'totalDue', 
            'totalPaid', 
            'totalRemaining', 
            'paymentRate', 
            'schoolYear',
            'userName',
            'schoolLogoPath'
        ));

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('detail_eleve_' . $studentId . '_' . date('Y-m-d') . '.pdf');
    }
}