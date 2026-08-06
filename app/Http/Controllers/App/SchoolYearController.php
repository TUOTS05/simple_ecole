<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class SchoolYearController extends Controller
{
    public function index()
    {
        $schoolId = session('current_school_id');
        $schoolYears = SchoolYear::where('school_id', $schoolId)
            ->orderBy('start_date', 'desc')
            ->get();

        return view('app.school-years.index', compact('schoolYears'));
    }

    public function create()
    {
        return view('app.school-years.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:20',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        $schoolId = session('current_school_id');
        $validated['school_id'] = $schoolId;

        // Si cette année est active, désactiver les autres
        if ($validated['is_active'] ?? false) {
            SchoolYear::where('school_id', $schoolId)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        SchoolYear::create($validated);

        return redirect()->route('app.school-years.index')
            ->with('success', 'Année scolaire créée avec succès !');
    }

    public function show(SchoolYear $schoolYear)
    {
        if ($schoolYear->school_id !== session('current_school_id')) {
            abort(403);
        }

        $schoolYear->loadCount(['fees', 'enrollments']);

        return view('app.school-years.show', compact('schoolYear'));
    }

    public function edit(SchoolYear $schoolYear)
    {
        if ($schoolYear->school_id !== session('current_school_id')) {
            abort(403);
        }

        return view('app.school-years.edit', compact('schoolYear'));
    }

    public function update(Request $request, SchoolYear $schoolYear)
    {
        if ($schoolYear->school_id !== session('current_school_id')) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:20',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        // Si cette année est active, désactiver les autres
        if ($validated['is_active'] ?? false) {
            SchoolYear::where('school_id', $schoolYear->school_id)
                ->where('id', '!=', $schoolYear->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $schoolYear->update($validated);

        return redirect()->route('app.school-years.index')
            ->with('success', 'Année scolaire mise à jour !');
    }

    public function destroy(SchoolYear $schoolYear)
    {
        if ($schoolYear->school_id !== session('current_school_id')) {
            abort(403);
        }

        if ($schoolYear->enrollments()->count() > 0) {
            return redirect()->route('app.school-years.index')
                ->with('error', 'Impossible de supprimer cette année car elle contient des inscriptions.');
        }

        $schoolYear->delete();

        return redirect()->route('app.school-years.index')
            ->with('success', 'Année scolaire supprimée !');
    }
}