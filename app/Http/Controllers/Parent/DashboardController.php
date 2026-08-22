<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\SchoolYear;
use App\Models\Enrollment;
use App\Models\Attendance;
use App\Models\ReportCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Dashboard parent : liste des enfants avec statistiques
     */
    public function index(Request $request)
    {
        $parent = auth()->user();

        // Année scolaire sélectionnée (par défaut : année active). Un parent peut avoir des
        // enfants dans des écoles différentes, donc il n'existe pas d'"année courante" unique :
        // chaque enfant doit résoudre l'année scolaire de SA propre école.
        $selectedYearId = $request->get('year');

        // Récupérer tous les enfants de ce parent
        $children = $parent->children()->with('school')->get();

        // Pour chaque enfant, récupérer les données de l'année scolaire de son école
        $childrenData = $children->map(function($child) use ($selectedYearId) {
            $currentYear = $this->resolveYearForChild($child, $selectedYearId);

            return $this->resolveChildStats($child, $currentYear);
        });
        
        // Grouper par école
        $childrenBySchool = $childrenData->groupBy(function($data) {
            return $data['student']->school_id;
        });
        
        // Années scolaires des écoles où ce parent a effectivement des enfants (pas toutes les
        // écoles de la plateforme)
        $schoolYears = SchoolYear::whereIn('school_id', $children->pluck('school_id')->unique())
            ->orderBy('start_date', 'desc')
            ->get();
        
        // Statistiques globales
        $globalStats = [
            'totalChildren' => $childrenData->count(),
            'totalFees' => $childrenData->sum(function($data) {
                return $data['enrollment']->tuition_fee_total ?? 0;
            }),
            'totalPaid' => $childrenData->sum(function($data) {
                return $data['enrollment']->tuition_fee_paid ?? 0;
            }),
            'averageAttendance' => $childrenData->avg('attendanceRate'),
        ];
        
        return view('parent.dashboard', compact(
            'childrenBySchool',
            'schoolYears',
            'globalStats'
        ));
    }
    
    /**
     * Détails d'un enfant spécifique
     */
    public function childDetails(Request $request, $studentId)
    {
        $parent = auth()->user();
        
        // Vérifier que ce parent a bien accès à cet élève
        $student = $parent->children()
            ->where('students.id', $studentId)
            ->with('school')
            ->firstOrFail();

        // Années scolaires disponibles pour l'école de cet enfant (pour le sélecteur)
        $schoolYears = SchoolYear::where('school_id', $student->school_id)
            ->orderBy('start_date', 'desc')
            ->get();

        $currentYear = $this->resolveYearForChild($student, $request->get('year'));

        $data = $this->resolveChildStats($student, $currentYear);

        return view('parent.child-details', array_merge($data, [
            'currentYear' => $currentYear,
            'schoolYears' => $schoolYears,
        ]));
    }

    /**
     * Résout l'année scolaire à considérer pour un enfant donné : l'année demandée si elle
     * appartient bien à l'école de cet enfant, sinon l'année active de son école.
     */
    private function resolveYearForChild(Student $child, ?string $selectedYearId): ?SchoolYear
    {
        return $selectedYearId
            ? SchoolYear::where('id', $selectedYearId)->where('school_id', $child->school_id)->first()
            : SchoolYear::where('school_id', $child->school_id)->where('is_active', true)->first();
    }

    /**
     * Calcule les statistiques (inscription, classe, moyenne, taux de paiement, présence)
     * d'un enfant pour une année scolaire donnée. Partagé entre le dashboard et la fiche détail.
     */
    private function resolveChildStats(Student $child, ?SchoolYear $currentYear): array
    {
        $data = [
            'student' => $child,
            'enrollment' => null,
            'schoolClass' => null,
            'average' => null,
            'paymentRate' => 0,
            'attendanceRate' => 0,
            'totalDays' => 0,
            'presentDays' => 0,
        ];

        if (!$currentYear) {
            return $data;
        }

        // Récupérer l'inscription pour cette année
        $enrollment = Enrollment::where('student_id', $child->id)
            ->where('school_year_id', $currentYear->id)
            ->with('schoolClass')
            ->first();

        if (!$enrollment) {
            return $data;
        }

        $data['enrollment'] = $enrollment;
        $data['schoolClass'] = $enrollment->schoolClass;

        // Calculer le taux de paiement
        if ($enrollment->tuition_fee_total > 0) {
            $data['paymentRate'] = round(
                ($enrollment->tuition_fee_paid / $enrollment->tuition_fee_total) * 100,
                1
            );
        }

        // Calculer la moyenne générale (bulletin le plus récent de l'année :
        // un élève a un bulletin par période, donc on trie par updated_at)
        $reportCard = ReportCard::where('student_id', $child->id)
            ->where('school_year_id', $currentYear->id)
            ->whereNotNull('average')
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($reportCard) {
            $data['average'] = round($reportCard->average, 2);
        }

        // Calculer le taux de présence (30 derniers jours)
        $thirtyDaysAgo = now()->subDays(30);
        $attendanceStats = Attendance::where('student_id', $child->id)
            ->where('date', '>=', $thirtyDaysAgo)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $totalDays = $attendanceStats->sum();
        $presentDays = $attendanceStats['present'] ?? 0;

        $data['totalDays'] = $totalDays;
        $data['presentDays'] = $presentDays;

        if ($totalDays > 0) {
            $data['attendanceRate'] = round(($presentDays / $totalDays) * 100, 1);
        }

        return $data;
    }
}