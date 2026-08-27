<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\Request;

class ParentController extends Controller
{
    /**
     * Annuaire des parents : tous, filtrés par classe, ou recherchés par nom/email,
     * avec les enfants liés affichés directement pour savoir qui est parent de qui.
     */
    public function index(Request $request)
    {
        $schoolId = session('current_school_id');

        $classes = SchoolClass::where('school_id', $schoolId)->orderBy('name')->get();

        $query = User::where('school_id', $schoolId)->where('role', 'parent');

        if ($request->filled('class_id')) {
            $query->whereHas('children.classes', function ($q) use ($request) {
                $q->where('school_classes.id', $request->class_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
            });
        }

        $parents = $query->with(['children' => function ($q) {
            $q->with('classes')->orderBy('first_name');
        }])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);

        $parents->appends($request->query());

        return view('app.parents.index', compact('parents', 'classes'));
    }

    /**
     * Fiche d'un parent : coordonnées et détail de chaque enfant lié.
     */
    public function show(User $parentUser)
    {
        if ($parentUser->school_id !== session('current_school_id') || $parentUser->role !== 'parent') {
            abort(403, 'Accès non autorisé à ce parent.');
        }

        $parentUser->load(['children.classes', 'children.enrollments.schoolYear']);

        return view('app.parents.show', ['parent' => $parentUser]);
    }
}
