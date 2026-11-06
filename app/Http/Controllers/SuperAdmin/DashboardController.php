<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Contract;
use App\Models\School;
use Carbon\Carbon;

/**
 * Super-administration : tableau de bord de la plateforme.
 */
class DashboardController extends Controller
{
    /**
     * Statistiques de la plateforme : écoles par statut et par offre, utilisateurs, revenus.
     */
    public function index()
    {
        // 1. Statistiques des écoles
        $totalSchools = School::count();
        $activeSchools = School::where('status', 'active')->count();
        $suspendedSchools = School::where('status', 'suspended')->count();
        $pendingSchools = School::where('status', 'pending')->count();

        // 2. Statistiques des contrats
        $totalContracts = Contract::count();
        $activeContracts = Contract::where('status', 'active')->count();
        $expiredContracts = Contract::where('status', 'expired')->count();
        $renewedContracts = Contract::where('status', 'renewed')->count();

        // 3. Revenus (somme des contrats actifs + renouvelés = historique complet)
        $totalRevenue = Contract::whereIn('status', ['active', 'renewed'])->sum('amount');
        $monthlyRevenue = Contract::whereIn('status', ['active', 'renewed'])
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('amount');

        // 4. Contrats expirant dans les 30 prochains jours (alerte)
        $expiringSoon = Contract::where('status', 'active')
            ->whereBetween('end_date', [Carbon::now(), Carbon::now()->addDays(30)])
            ->with('school')
            ->orderBy('end_date')
            ->take(5)
            ->get();

        // 5. Dernières activités (logs)
        $recentActivities = ActivityLog::orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // 6. Dernières écoles créées
        $recentSchools = School::orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('superadmin.dashboard', compact(
            'totalSchools', 'activeSchools', 'suspendedSchools', 'pendingSchools',
            'totalContracts', 'activeContracts', 'expiredContracts', 'renewedContracts',
            'totalRevenue', 'monthlyRevenue',
            'expiringSoon', 'recentActivities', 'recentSchools'
        ));
    }
}
