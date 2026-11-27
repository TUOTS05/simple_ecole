<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $classes = $query->orderBy('cycle')->orderBy('name')->paginate(15);
        $classes->appends($request->query());

        return view('app.classes.index', compact('classes', 'school'));
    }

    public function create()
    {
        $school = $this->school();

        return view('app.classes.create', [
            'school' => $school,
            'class' => new SchoolClass,
            'cycles' => $this->cyclesFor($school),
            'levelsByCycle' => $school->levelsByCycle(),
            'responsibles' => $this->responsibleCandidates(),
        ]);
    }

    public function store(Request $request)
    {
        $school = $this->school();
        $validated = $this->validateClass($request, $school);

        if ($validated instanceof RedirectResponse) {
            return $validated;
        }

        // Vérifier qu'une classe avec le même nom n'existe pas déjà
        $exists = SchoolClass::where('school_id', $school->id)
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'Une classe avec ce nom existe déjà.'])
                ->withInput();
        }

        $validated['school_id'] = $school->id;

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
            ->whereHas('classes', function ($q) use ($class) {
                $q->where('school_classes.id', $class->id);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('app.classes.show', compact('class', 'students'));
    }

    public function edit(SchoolClass $class)
    {
        if ($class->school_id !== session('current_school_id')) {
            abort(403);
        }

        $school = $this->school();

        // Un niveau ou un cycle qui n'est plus proposé (groupe d'âge renommé, ancien « CP »...)
        // reste sélectionnable pour la classe qui le porte déjà.
        $levelsByCycle = $school->levelsByCycle();
        if ($class->cycle && ! in_array($class->level, $levelsByCycle[$class->cycle] ?? [], true)) {
            $levelsByCycle[$class->cycle] = [...($levelsByCycle[$class->cycle] ?? []), $class->level];
        }

        return view('app.classes.edit', [
            'school' => $school,
            'class' => $class,
            'cycles' => $this->cyclesFor($school, $class),
            'levelsByCycle' => $levelsByCycle,
            'responsibles' => $this->responsibleCandidates(),
        ]);
    }

    public function update(Request $request, SchoolClass $class)
    {
        if ($class->school_id !== session('current_school_id')) {
            abort(403);
        }

        $validated = $this->validateClass($request, $this->school(), $class);

        if ($validated instanceof RedirectResponse) {
            return $validated;
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

    private function school(): School
    {
        return School::findOrFail(session('current_school_id'));
    }

    /** Cycles proposés : les structures actives de l'école (+ le cycle actuel de la classe). */
    private function cyclesFor(School $school, ?SchoolClass $class = null): array
    {
        $cycles = array_intersect_key(School::STRUCTURES, array_flip($school->structureList()));

        if ($class?->cycle && isset(School::STRUCTURES[$class->cycle])) {
            $cycles[$class->cycle] = School::STRUCTURES[$class->cycle];
        }

        // Ordre du parcours : crèche, maternelle, primaire
        return array_intersect_key(School::STRUCTURES, $cycles);
    }

    /** Personnel pouvant être responsable d'un groupe de crèche. */
    private function responsibleCandidates()
    {
        return User::where('school_id', session('current_school_id'))
            ->whereIn('role', ['school_admin', ...array_keys(Permissions::ROLES)])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Valide le formulaire d'une classe. Retourne les données à enregistrer, ou une redirection
     * avec l'erreur quand le niveau ne correspond pas au cycle.
     */
    private function validateClass(Request $request, School $school, ?SchoolClass $class = null): array|RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
            'level' => 'required|string|max:30',
            'cycle' => ['required', Rule::in(array_keys($this->cyclesFor($school, $class)))],
            'capacity' => 'nullable|integer|min:1|max:100',
            // Groupe de crèche
            'age_min_months' => 'nullable|integer|min:0|max:72',
            'age_max_months' => 'nullable|integer|min:0|max:72',
            'room' => 'nullable|string|max:100',
            'responsible_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('school_id', $school->id)],
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
        ], [
            'cycle.in' => 'Ce cycle n\'est pas activé pour votre école.',
            'responsible_user_id.exists' => 'Ce responsable ne fait pas partie du personnel de l\'école.',
        ]);

        $cycleLabel = strtolower(School::STRUCTURES[$validated['cycle']]);
        $acceptedLevels = $school->acceptedLevelsFor($validated['cycle']);
        $keepsCurrentLevel = $class && $class->cycle === $validated['cycle'] && $class->level === $validated['level'];

        if (! $keepsCurrentLevel && ! in_array($validated['level'], $acceptedLevels, true)) {
            return back()->withErrors(['level' => "Ce niveau n'appartient pas au cycle {$cycleLabel}."])->withInput();
        }

        if ($mismatch = $this->nameLevelMismatch($validated['name'], $validated['level'])) {
            return back()->withErrors(['level' => $mismatch])->withInput();
        }

        if ($validated['cycle'] === 'creche') {
            if (isset($validated['age_min_months'], $validated['age_max_months']) && $validated['age_max_months'] < $validated['age_min_months']) {
                return back()->withErrors(['age_max_months' => "L'âge maximum doit être supérieur ou égal à l'âge minimum."])->withInput();
            }
            if (! empty($validated['opening_time']) && ! empty($validated['closing_time']) && $validated['closing_time'] <= $validated['opening_time']) {
                return back()->withErrors(['closing_time' => "L'heure de fermeture doit être après l'heure d'ouverture."])->withInput();
            }
        } else {
            // Ces informations n'ont de sens que pour un groupe de crèche.
            foreach (['age_min_months', 'age_max_months', 'room', 'responsible_user_id', 'opening_time', 'closing_time'] as $field) {
                $validated[$field] = null;
            }
        }

        // Capacité optionnelle : la colonne est NOT NULL (défaut 25), on n'envoie jamais null.
        $validated['capacity'] = $validated['capacity'] ?? SchoolClass::DEFAULT_CAPACITY;

        return $validated;
    }

    /**
     * Détecte un nom de classe qui contredit le niveau choisi (ex. "CM2 A" enregistrée en
     * Maternelle / GS suite à une mauvaise sélection dans les listes déroulantes).
     * Seuls les codes de niveau écrits comme mots isolés dans le nom sont pris en compte ;
     * un nom sans code ("Petite section", "Les Lutins") n'est jamais bloqué.
     */
    private function nameLevelMismatch(string $name, string $level): ?string
    {
        $codes = ['TPS', 'PS', 'MS', 'GS', 'CP', 'CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2'];
        // CP et CP1 désignent la même année
        $normalize = fn (string $code) => $code === 'CP' ? 'CP1' : $code;

        $tokens = preg_split('/[^A-Za-z0-9]+/', strtoupper($name), -1, PREG_SPLIT_NO_EMPTY);
        $levelUpper = strtoupper($level);

        foreach ($tokens as $token) {
            if (in_array($token, $codes, true) && $normalize($token) !== $normalize($levelUpper)) {
                return "Le nom « {$name} » correspond au niveau {$token}, mais le niveau choisi est {$level}. Vérifiez le cycle et le niveau sélectionnés.";
            }
        }

        return null;
    }
}
