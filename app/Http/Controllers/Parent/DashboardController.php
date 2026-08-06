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
        
        // Récupérer l'année scolaire sélectionnée (par défaut : année active)
        $selectedYearId = $request->get('year');
        
        if ($selectedYearId) {
            $currentYear = SchoolYear::find($selectedYearId);
        } else {
            $currentYear = SchoolYear::where('is_active', true)->first();
        }
        
        // Récupérer tous les enfants de ce parent
        $children = $parent->children()->with('school')->get();
        
        // Pour chaque enfant, récupérer les données de l'année sélectionnée
        $childrenData = $children->map(function($child) use ($currentYear) {
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
            
            if ($currentYear) {
                // Récupérer l'inscription pour cette année
                $enrollment = Enrollment::where('student_id', $child->id)
                    ->where('school_year_id', $currentYear->id)
                    ->with('schoolClass')
                    ->first();
                
                if ($enrollment) {
                    $data['enrollment'] = $enrollment;
                    $data['schoolClass'] = $enrollment->schoolClass;
                    
                    // Calculer le taux de paiement
                    if ($enrollment->tuition_fee_total > 0) {
                        $data['paymentRate'] = round(
                            ($enrollment->tuition_fee_paid / $enrollment->tuition_fee_total) * 100, 
                            1
                        );
                    }
                    
                    // Calculer la moyenne générale
                    $reportCard = ReportCard::where('student_id', $child->id)
                        ->where('school_year_id', $currentYear->id)
                        ->first();
                    
                    if ($reportCard && $reportCard->grades_count > 0) {
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
                }
            }
            
            return $data;
        });
        
        // Grouper par école
        $childrenBySchool = $childrenData->groupBy(function($data) {
            return $data['student']->school_id;
        });
        
        // Récupérer toutes les années scolaires pour le sélecteur
        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();
        
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
            'currentYear', 
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
        
        // Année scolaire sélectionnée
        $selectedYearId = $request->get('year');
        
        if ($selectedYearId) {
            $currentYear = SchoolYear::find($selectedYearId);
        } else {
            $currentYear = SchoolYear::where('school_id', $student->school_id)
                ->where('is_active', true)
                ->first();
        }
        
        // Récupérer l'inscription pour cette année
        $enrollment = null;
        $schoolClass = null;
        
        if ($currentYear) {
            $enrollment = Enrollment::where('student_id', $studentId)
                ->where('school_year_id', $currentYear->id)
                ->with('schoolClass')
                ->first();
            
            if ($enrollment) {
                $schoolClass = $enrollment->schoolClass;
            }
        }
        
        return view('parent.child-details', compact('student', 'currentYear', 'enrollment', 'schoolClass'));
    }
}