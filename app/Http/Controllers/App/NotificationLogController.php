<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Illuminate\Http\Request;

class NotificationLogController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = session('current_school_id');

        $query = NotificationLog::where('school_id', $schoolId)
            ->with(['student', 'parent', 'installment'])
            ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('recipient_phone', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhereHas('student', function($q2) use ($search) {
                      $q2->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $notifications = $query->paginate(20);

        return view('app.notifications.index', compact('notifications'));
    }
}