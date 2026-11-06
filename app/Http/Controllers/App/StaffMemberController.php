<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\StaffFunction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Comptes du personnel administratif (rôle « staff »), dont les accès découlent de la
 * fonction qui leur est attribuée (voir StaffFunctionController).
 */
class StaffMemberController extends Controller
{
    /**
     * Liste du personnel administratif de l'école, avec la fonction de chacun.
     */
    public function index()
    {
        // Tout le personnel de l'école : administratif (avec sa fonction) et enseignants
        $staff = User::where('school_id', session('current_school_id'))
            ->whereIn('role', ['staff', 'teacher'])
            ->with('staffFunction')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);

        return view('app.staff.index', compact('staff'));
    }

    /**
     * Formulaire de création d'un compte du personnel.
     */
    public function create(Request $request)
    {
        return view('app.staff.form', [
            'member' => new User,
            'functions' => $this->functions(),
            'modules' => config('school_permissions.modules'),
            'profile' => $request->query('profile') === 'teacher' ? 'teacher' : 'staff',
        ]);
    }

    /**
     * Crée un compte du personnel, dans la limite d'utilisateurs de l'abonnement.
     */
    public function store(Request $request)
    {
        $validated = $this->validated($request);

        $school = School::find(session('current_school_id'));
        if ($school && $school->hasReachedUserLimit()) {
            return back()->withErrors([
                'email' => "Le plafond de {$school->max_users} utilisateurs de votre abonnement est atteint. Contactez le support pour augmenter votre plan.",
            ])->withInput();
        }

        // role, school_id et staff_function_id ne sont pas mass-assignables (protection contre
        // l'élévation de privilèges) : affectation explicite.
        $member = DB::transaction(function () use ($validated) {
            $member = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'],
            ]);
            $member->school_id = session('current_school_id');
            $member->role = $validated['profile'] ?? 'staff';
            $member->staff_function_id = $member->role === 'staff' ? $this->functionId($validated) : null;
            $member->save();

            return $member;
        });

        $next = $member->role === 'teacher' ? ' Affectez-le maintenant à ses classes (Personnel → Assignations).' : '';

        return redirect()->route('app.staff.index')
            ->with('success', "Compte de {$member->full_name} créé.{$next}");
    }

    /**
     * Formulaire de modification d'un compte du personnel.
     */
    public function edit(User $member)
    {
        $this->authorizeMember($member);

        return view('app.staff.form', [
            'member' => $member,
            'functions' => $this->functions(),
            'modules' => config('school_permissions.modules'),
            'profile' => 'staff',
        ]);
    }

    /**
     * Met à jour un compte du personnel ; le mot de passe n'est changé que s'il est saisi.
     */
    public function update(Request $request, User $member)
    {
        $this->authorizeMember($member);

        $validated = $this->validated($request, $member);

        $member->first_name = $validated['first_name'];
        $member->last_name = $validated['last_name'];
        $member->email = $validated['email'];
        $member->phone = $validated['phone'] ?? null;
        $member->gender = $validated['gender'];
        $member->staff_function_id = $this->functionId($validated);

        if (! empty($validated['password'])) {
            $member->password = Hash::make($validated['password']);
        }

        $member->save();

        return redirect()->route('app.staff.index')
            ->with('success', "Compte de {$member->full_name} mis à jour.");
    }

    /**
     * Supprime un compte du personnel.
     */
    public function destroy(User $member)
    {
        $this->authorizeMember($member);

        $member->delete();

        return redirect()->route('app.staff.index')
            ->with('success', 'Compte supprimé.');
    }

    /**
     * Fonction du membre : celle choisie, ou celle créée depuis le formulaire avec ses droits d'accès.
     */
    private function functionId(array $validated): int
    {
        if (empty($validated['new_function'])) {
            return (int) $validated['staff_function_id'];
        }

        $function = new StaffFunction([
            'name' => $validated['new_function_name'],
            'permissions' => array_values($validated['permissions'] ?? []),
        ]);
        $function->school_id = session('current_school_id');
        $function->save();

        return $function->id;
    }

    private function functions()
    {
        return StaffFunction::where('school_id', session('current_school_id'))->orderBy('name')->get();
    }

    /**
     * Valide le formulaire ; la fonction choisie doit appartenir à l'école.
     */
    private function validated(Request $request, ?User $member = null): array
    {
        $modules = array_keys(config('school_permissions.modules'));
        $isNew = ! $member;

        return $request->validate([
            // Profil : seulement à la création (un enseignant se modifie depuis la liste des enseignants)
            'profile' => [$isNew ? 'nullable' : 'prohibited', 'in:staff,teacher'], // par défaut : personnel administratif
            // Nouvelle fonction créée depuis ce formulaire, avec ses droits d'accès
            'new_function' => 'nullable|boolean',
            'new_function_name' => [
                'required_if:new_function,1', 'nullable', 'string', 'max:100',
                Rule::unique('staff_functions', 'name')->where('school_id', session('current_school_id')),
            ],
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::in($modules)],
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($member?->id)],
            'password' => [$member ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
            'phone' => 'nullable|string|max:20',
            'gender' => 'required|in:M,F',
            // Uniquement une fonction de l'école courante ; inutile pour un enseignant ou une nouvelle fonction
            'staff_function_id' => [
                Rule::requiredIf(($isNew ? $request->input('profile', 'staff') === 'staff' : true) && ! $request->boolean('new_function')),
                'nullable',
                Rule::exists('staff_functions', 'id')->where('school_id', session('current_school_id')),
            ],
        ]);
    }

    private function authorizeMember(User $member): void
    {
        abort_if($member->school_id !== session('current_school_id') || ! $member->isStaff(), 403);
    }
}
