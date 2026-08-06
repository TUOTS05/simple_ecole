<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
    public function index()
    {
        $schoolId = session('current_school_id');
        
        $teachers = Teacher::where('school_id', $schoolId)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);

        return view('app.teachers.index', compact('teachers'));
    }

    public function create()
    {
        return view('app.teachers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
            'gender' => 'required|in:M,F',
        ]);

        Teacher::create([
            'school_id' => session('current_school_id'),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'],
            'role' => 'teacher',
        ]);

        return redirect()->route('app.teachers.index')
            ->with('success', 'Enseignant créé avec succès !');
    }

    public function edit(Teacher $teacher)
    {
        if ($teacher->school_id !== session('current_school_id')) {
            abort(403);
        }

        return view('app.teachers.edit', compact('teacher'));
    }

    public function update(Request $request, Teacher $teacher)
    {
        if ($teacher->school_id !== session('current_school_id')) {
            abort(403);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $teacher->id,
            'password' => 'nullable|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
            'gender' => 'required|in:M,F',
        ]);

        $teacher->first_name = $validated['first_name'];
        $teacher->last_name = $validated['last_name'];
        $teacher->email = $validated['email'];
        $teacher->phone = $validated['phone'] ?? null;
        $teacher->gender = $validated['gender'];

        if (!empty($validated['password'])) {
            $teacher->password = Hash::make($validated['password']);
        }

        $teacher->save();

        return redirect()->route('app.teachers.index')
            ->with('success', 'Enseignant mis à jour avec succès !');
    }

    public function destroy(Teacher $teacher)
    {
        if ($teacher->school_id !== session('current_school_id')) {
            abort(403);
        }

        if ($teacher->assignments()->count() > 0) {
            return back()->withErrors(['error' => 'Impossible de supprimer cet enseignant car il est assigné à une classe.']);
        }

        $teacher->delete();

        return redirect()->route('app.teachers.index')
            ->with('success', 'Enseignant supprimé avec succès !');
    }
}