<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\StaffFunction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Fonctions administratives de l'école et modules auxquels chacune donne accès.
 */
class StaffFunctionController extends Controller
{
    /**
     * Fonctions administratives de l'école, avec leur nombre de membres et des suggestions prêtes à
     * l'emploi.
     */
    public function index()
    {
        $functions = StaffFunction::where('school_id', session('current_school_id'))
            ->withCount('members')
            ->orderBy('name')
            ->get();

        return view('app.staff-functions.index', [
            'functions' => $functions,
            'modules' => config('school_permissions.modules'),
            'suggested' => array_diff_key(config('school_permissions.suggested_functions'), $functions->keyBy('name')->all()),
        ]);
    }

    /**
     * Formulaire de création d'une fonction.
     */
    public function create()
    {
        return view('app.staff-functions.form', [
            'function' => new StaffFunction(['permissions' => []]),
            'modules' => config('school_permissions.modules'),
        ]);
    }

    /**
     * Crée une fonction pour l'école connectée.
     */
    public function store(Request $request)
    {
        $function = new StaffFunction($this->validated($request));
        $function->school_id = session('current_school_id');
        $function->save();

        return redirect()->route('app.staff-functions.index')
            ->with('success', "Fonction « {$function->name} » créée.");
    }

    /**
     * Crée en un clic les fonctions types de config/school_permissions.php encore absentes.
     */
    public function storeSuggested()
    {
        $schoolId = session('current_school_id');

        foreach (config('school_permissions.suggested_functions') as $name => $permissions) {
            $exists = StaffFunction::where('school_id', $schoolId)->where('name', $name)->exists();
            if (! $exists) {
                $function = new StaffFunction(['name' => $name, 'permissions' => $permissions]);
                $function->school_id = $schoolId;
                $function->save();
            }
        }

        return redirect()->route('app.staff-functions.index')
            ->with('success', 'Fonctions types ajoutées : adaptez-les librement.');
    }

    /**
     * Formulaire de modification d'une fonction et de ses accès.
     */
    public function edit(StaffFunction $staffFunction)
    {
        $this->authorizeSchool($staffFunction);

        return view('app.staff-functions.form', [
            'function' => $staffFunction,
            'modules' => config('school_permissions.modules'),
        ]);
    }

    /**
     * Met à jour une fonction et ses accès.
     */
    public function update(Request $request, StaffFunction $staffFunction)
    {
        $this->authorizeSchool($staffFunction);

        $staffFunction->update($this->validated($request, $staffFunction));

        return redirect()->route('app.staff-functions.index')
            ->with('success', "Fonction « {$staffFunction->name} » mise à jour.");
    }

    /**
     * Supprime une fonction, refusé tant qu'elle est attribuée à des membres du personnel.
     */
    public function destroy(StaffFunction $staffFunction)
    {
        $this->authorizeSchool($staffFunction);

        if ($staffFunction->members()->exists()) {
            return back()->withErrors(['function' => 'Cette fonction est encore attribuée à des membres du personnel : changez-leur de fonction avant de la supprimer.']);
        }

        $staffFunction->delete();

        return redirect()->route('app.staff-functions.index')
            ->with('success', 'Fonction supprimée.');
    }

    /**
     * Valide le formulaire : nom unique dans l'école, accès limités aux modules connus.
     */
    private function validated(Request $request, ?StaffFunction $function = null): array
    {
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('staff_functions')->where('school_id', session('current_school_id'))->ignore($function?->id),
            ],
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::in(array_keys(config('school_permissions.modules')))],
        ]);
        $validated['permissions'] = array_values(array_unique($validated['permissions'] ?? []));

        return $validated;
    }

    private function authorizeSchool(StaffFunction $function): void
    {
        abort_if($function->school_id !== session('current_school_id'), 403);
    }
}
