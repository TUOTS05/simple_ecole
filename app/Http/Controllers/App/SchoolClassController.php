<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;

class SchoolClassController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = session('current_school_id');
        $school = session('current_school');
        
        $query = SchoolClass::where('school_id', $schoolId)
            ->withCount('students');
        
        // Filtre par cycle
        if ($request->filled('cycle')) {
            $query->where('cycle', $request->cycle);
        }
        
        // Recherche par nom
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        $classes = $query->orderBy('cycle')->orderBy('name')->paginate(15);
        $classes->appends($request->query());
        
        return view('app.classes.index', compact('classes', 'school'));
    }

    // public function create()
    // {
    //     $school = session('current_school');
    //     $allowedLevels = $school->getAllowedLevels();
        
    //     // Grouper les niveaux par cycle
    //     $levelsByCycle = [
    //         'maternelle' => array_intersect($allowedLevels, ['TPS', 'PS', 'MS', 'GS']),
    //         'primaire' => array_intersect($allowedLevels, ['CP', 'CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2']),
    //     ];
        
    //     return view('app.classes.create', compact('school', 'levelsByCycle'));
    // }

        public function create()
    {
        $school = session('current_school');
        
        // Définir les niveaux selon le type d'école
        $levelsByCycle = $this->getLevelsByCycle($school);
        
        return view('app.classes.create', compact('school', 'levelsByCycle'));
    }



    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'required|string|max:10',
            'cycle' => 'required|in:maternelle,primaire',
            'capacity' => 'nullable|integer|min:1|max:100',
        ]);
        
        $schoolId = session('current_school_id');
        
        // Vérifier que le niveau correspond au cycle
        $maternelleLevels = ['TPS', 'PS', 'MS', 'GS'];
        $primaireLevels = ['CP', 'CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2'];
        
        if ($validated['cycle'] === 'maternelle' && !in_array($validated['level'], $maternelleLevels)) {
            return back()->withErrors(['level' => 'Ce niveau n\'appartient pas au cycle maternelle.'])
                ->withInput();
        }
        
        if ($validated['cycle'] === 'primaire' && !in_array($validated['level'], $primaireLevels)) {
            return back()->withErrors(['level' => 'Ce niveau n\'appartient pas au cycle primaire.'])
                ->withInput();
        }
        
        // Vérifier qu'une classe avec le même nom n'existe pas déjà
        $exists = SchoolClass::where('school_id', $schoolId)
            ->where('name', $validated['name'])
            ->exists();
        
        if ($exists) {
            return back()->withErrors(['name' => 'Une classe avec ce nom existe déjà.'])
                ->withInput();
        }
        
        $validated['school_id'] = $schoolId;
        
        SchoolClass::create($validated);
        
        return redirect()->route('app.classes.index')
            ->with('success', 'Classe créée avec succès !');
    }

    public function show(SchoolClass $class)
    {
        if ($class->school_id !== session('current_school_id')) {
            abort(403);
        }
        
        $class->loadCount('students');
        
        $students = Student::where('school_id', session('current_school_id'))
            ->whereHas('classes', function($q) use ($class) {
                $q->where('school_classes.id', $class->id);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
        
        return view('app.classes.show', compact('class', 'students'));
    }

    // public function edit(SchoolClass $class)
    // {
    //     if ($class->school_id !== session('current_school_id')) {
    //         abort(403);
    //     }
        
    //     $school = session('current_school');
    //     $allowedLevels = $school->getAllowedLevels();
        
    //     $levelsByCycle = [
    //         'maternelle' => array_intersect($allowedLevels, ['TPS', 'PS', 'MS', 'GS']),
    //         'primaire' => array_intersect($allowedLevels, ['CP', 'CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2']),
    //     ];
        
    //     return view('app.classes.edit', compact('class', 'school', 'levelsByCycle'));
    // }

        public function edit(SchoolClass $class)
    {
        if ($class->school_id !== session('current_school_id')) {
            abort(403);
        }
        
        $school = session('current_school');
        $levelsByCycle = $this->getLevelsByCycle($school);
        
        return view('app.classes.edit', compact('class', 'school', 'levelsByCycle'));
    }

    public function update(Request $request, SchoolClass $class)
    {
        if ($class->school_id !== session('current_school_id')) {
            abort(403);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'required|string|max:10',
            'cycle' => 'required|in:maternelle,primaire',
            'capacity' => 'nullable|integer|min:1|max:100',
        ]);
        
        // Vérifier que le niveau correspond au cycle
        $maternelleLevels = ['TPS', 'PS', 'MS', 'GS'];
        $primaireLevels = ['CP', 'CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2'];
        
        if ($validated['cycle'] === 'maternelle' && !in_array($validated['level'], $maternelleLevels)) {
            return back()->withErrors(['level' => 'Ce niveau n\'appartient pas au cycle maternelle.'])
                ->withInput();
        }
        
        if ($validated['cycle'] === 'primaire' && !in_array($validated['level'], $primaireLevels)) {
            return back()->withErrors(['level' => 'Ce niveau n\'appartient pas au cycle primaire.'])
                ->withInput();
        }
        
        // Vérifier qu'une classe avec le même nom n'existe pas déjà (sauf la classe actuelle)
        $exists = SchoolClass::where('school_id', $class->school_id)
            ->where('name', $validated['name'])
            ->where('id', '!=', $class->id)
            ->exists();
        
        if ($exists) {
            return back()->withErrors(['name' => 'Une classe avec ce nom existe déjà.'])
                ->withInput();
        }
        
        $class->update($validated);
        
        return redirect()->route('app.classes.index')
            ->with('success', 'Classe mise à jour !');
    }

    public function destroy(SchoolClass $class)
    {
        if ($class->school_id !== session('current_school_id')) {
            abort(403);
        }
        
        if ($class->students()->count() > 0) {
            return redirect()->route('app.classes.index')
                ->with('error', 'Impossible de supprimer cette classe car elle contient des élèves.');
        }
        
        $class->delete();
        
        return redirect()->route('app.classes.index')
            ->with('success', 'Classe supprimée !');
    }

        /**
     * Récupérer les niveaux disponibles par cycle selon le type d'école
     */
    private function getLevelsByCycle($school): array
    {
        $maternelleLevels = ['TPS', 'PS', 'MS', 'GS'];
        $primaireLevels = ['CP', 'CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2'];
        
        $levelsByCycle = [];
        
        // Si l'école est maternelle ou both, ajouter les niveaux maternelle
        if ($school->school_type === 'maternelle' || $school->school_type === 'both') {
            $levelsByCycle['maternelle'] = $maternelleLevels;
        }
        
        // Si l'école est primaire ou both, ajouter les niveaux primaire
        if ($school->school_type === 'primaire' || $school->school_type === 'both') {
            $levelsByCycle['primaire'] = $primaireLevels;
        }
        
        return $levelsByCycle;
    }
}