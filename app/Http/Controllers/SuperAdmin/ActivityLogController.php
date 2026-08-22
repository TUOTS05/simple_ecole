<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    public function index()
    {
        // Récupère les 100 dernières actions, triées de la plus récente à la plus ancienne
        $logs = ActivityLog::orderBy('created_at', 'desc')->take(100)->get();
        
        return view('superadmin.activity-logs.index', compact('logs'));
    }
}