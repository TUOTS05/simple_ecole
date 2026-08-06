<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use App\Models\Contract;        
use App\Models\ActivityLog;      

class DashboardController extends Controller
{
    // public function index()
    // {
    //     // Statistiques globales des écoles
    //     $totalSchools = School::count();
    //     $activeSchools = School::where('status', 'active')->where(function($q) {
    //         $q->whereNull('subscription_end_date')
    //           ->orWhereDate('subscription_end_date', '>=', Carbon::today());
    //     })->count();
        
    //     $suspendedSchools = School::where('status', 'suspended')->count();
    //     $expiredSchools = School::where(function($q) {
    //         $q->where('status', 'expired')
    //           ->orWhereDate('subscription_end_date', '<', Carbon::today());
    //     })->count();

    //     // Écoles expirant bientôt (dans les 30 prochains jours)
    //     $expiringSoonSchools = School::where('status', 'active')
    //         ->whereBetween('subscription_end_date', [Carbon::today(), Carbon::today()->addDays(30)])
    //         ->orderBy('subscription_end_date')
    //         ->limit(5)
    //         ->get();

    //     // Répartition par plan d'abonnement
    //     $schoolsByPlan = School::select('subscription_plan')
    //         ->selectRaw('count(*) as count')
    //         ->groupBy('subscription_plan')
    //         ->pluck('count', 'subscription_plan')
    //         ->toArray();

    //     // Statistiques des utilisateurs
    //     $totalUsers = User::count();
    //     $superAdmins = User::where('role', 'super_admin')->count();
    //     $schoolAdmins = User::where('role', 'school_admin')->count();
    //     $teachers = User::where('role', 'teacher')->count();
    //     $parents = User::where('role', 'parent')->count();

    //     // Revenus estimés (basés sur les plans actifs)
    //     $plans = SubscriptionPlan::active()->get();
    //     $monthlyRevenue = 0;
    //     $yearlyRevenue = 0;
        
    //     foreach ($schoolsByPlan as $planSlug => $count) {
    //         $plan = $plans->where('slug', $planSlug)->first();
    //         if ($plan) {
    //             $monthlyRevenue += $plan->monthly_price * $count;
    //             $yearlyRevenue += $plan->yearly_price * $count;
    //         }
    //     }

    //     // Dernières écoles créées
    //     $recentSchools = School::orderBy('created_at', 'desc')->limit(5)->get();

    //     return view('superadmin.dashboard', compact(
    //         'totalSchools',
    //         'activeSchools',
    //         'suspendedSchools',
    //         'expiredSchools',
    //         'expiringSoonSchools',
    //         'schoolsByPlan',
    //         'totalUsers',
    //         'superAdmins',
    //         'schoolAdmins',
    //         'teachers',
    //         'parents',
    //         'monthlyRevenue',
    //         'yearlyRevenue',
    //         'recentSchools'
    //     ));
    // }

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