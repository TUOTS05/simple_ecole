<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Écran unifié « Personnel » : création de tout membre du personnel (enseignant, secrétaire,
 * comptable, responsables cantine/transport) avec son rôle et ses droits.
 *
 * Droits : par défaut ceux du rôle (users.permissions = NULL). Si le directeur choisit
 * « Personnaliser », la liste cochée est enregistrée et remplace les droits du rôle.
 */
class StaffController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('school_id', session('current_school_id'))
            ->whereIn('role', array_keys(Permissions::ROLES));

        if ($request->filled('role') && array_key_exists($request->role, Permissions::ROLES)) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $staff = $query->orderBy('last_name')->orderBy('first_name')->paginate(20)->withQueryString();

        $countsByRole = User::where('school_id', session('current_school_id'))
            ->whereIn('role', array_keys(Permissions::ROLES))
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $school = School::find(session('current_school_id'));

        return view('app.staff.index', [
            'staff' => $staff,
            'roles' => Permissions::ROLES,
            'countsByRole' => $countsByRole,
            'school' => $school,
        ]);
    }

    public function create(Request $request)
    {
        $role = array_key_exists($request->query('role'), Permissions::ROLES) ? $request->query('role') : 'teacher';

        return view('app.staff.create', $this->formData(new User, $role));
    }

    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        $school = School::find(session('current_school_id'));
        if ($school && $school->hasReachedUserLimit()) {
            return back()->withErrors([
                'email' => "Le plafond de {$school->max_users} utilisateurs de votre abonnement est atteint. Contactez le support pour augmenter votre plan.",
            ])->withInput();
        }

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'],
        ]);
        // role, school_id et permissions ne sont pas mass-assignables (protection contre
        // l'élévation de privilèges) : on les affecte explicitement.
        $user->school_id = session('current_school_id');
        $user->role = $validated['role'];
        $user->permissions = $this->permissionsFromRequest($request);
        $user->save();

        return redirect()->route('app.staff.index')
            ->with('success', "{$user->role_label} « {$user->full_name} » créé(e) avec succès !");
    }

    public function edit(User $staffUser)
    {
        $this->authorizeStaff($staffUser);

        return view('app.staff.edit', $this->formData($staffUser, $staffUser->role));
    }

    public function update(Request $request, User $staffUser)
    {
        $this->authorizeStaff($staffUser);

        $validated = $this->validateRequest($request, $staffUser);

        if ($staffUser->role === 'teacher' && $validated['role'] !== 'teacher' && $this->hasAssignments($staffUser)) {
            return back()->withErrors([
                'role' => "Cet enseignant est assigné à une ou plusieurs classes : retirez d'abord ses assignations avant de changer son rôle.",
            ])->withInput();
        }

        $staffUser->first_name = $validated['first_name'];
        $staffUser->last_name = $validated['last_name'];
        $staffUser->email = $validated['email'];
        $staffUser->phone = $validated['phone'] ?? null;
        $staffUser->gender = $validated['gender'];
        $staffUser->role = $validated['role'];
        $staffUser->permissions = $this->permissionsFromRequest($request);

        if (! empty($validated['password'])) {
            $staffUser->password = Hash::make($validated['password']);
        }

        $staffUser->save();

        return redirect()->route('app.staff.index')
            ->with('success', "« {$staffUser->full_name} » mis(e) à jour avec succès !");
    }

    public function destroy(User $staffUser)
    {
        $this->authorizeStaff($staffUser);

        if ($staffUser->role === 'teacher' && $this->hasAssignments($staffUser)) {
            return back()->withErrors(['error' => 'Impossible de supprimer cet enseignant car il est assigné à une classe.']);
        }

        $name = $staffUser->full_name;
        $staffUser->delete();

        return redirect()->route('app.staff.index')
            ->with('success', "« {$name} » a été supprimé(e).");
    }

    /** Seul le personnel de l'école courante est modifiable ici (jamais un directeur ou un parent). */
    private function authorizeStaff(User $user): void
    {
        if ($user->school_id !== session('current_school_id') || ! $user->isStaff()) {
            abort(403);
        }
    }

    private function hasAssignments(User $user): bool
    {
        return TeacherAssignment::where('user_id', $user->id)->exists();
    }

    private function validateRequest(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
            'phone' => 'nullable|string|max:20',
            'gender' => 'required|in:M,F',
            'role' => ['required', Rule::in(array_keys(Permissions::ROLES))],
            'permissions_mode' => 'required|in:default,custom',
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::in(Permissions::all())],
        ], [
            'role.required' => 'Choisissez un rôle.',
            'role.in' => 'Ce rôle n\'existe pas.',
            'permissions.*.in' => 'Un des droits cochés n\'existe pas.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'password.confirmed' => 'Les deux mots de passe ne correspondent pas.',
            'password.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
        ]);
    }

    /** NULL = droits du rôle ; sinon la liste cochée (éventuellement vide). */
    private function permissionsFromRequest(Request $request): ?array
    {
        if ($request->input('permissions_mode') !== 'custom') {
            return null;
        }

        return Permissions::sanitize((array) $request->input('permissions', []));
    }

    private function formData(User $user, string $role): array
    {
        return [
            'staffUser' => $user,
            'selectedRole' => old('role', $role),
            'roles' => Permissions::ROLES,
            'groups' => Permissions::GROUPS,
            'roleDefaults' => Permissions::ROLE_DEFAULTS,
            'permissionsMode' => old('permissions_mode', $user->hasCustomPermissions() ? 'custom' : 'default'),
            // Après une erreur de validation, on réaffiche exactement les cases cochées (même aucune).
            'checkedPermissions' => session()->hasOldInput()
                ? (array) old('permissions', [])
                : ($user->exists ? $user->effectivePermissions() : Permissions::defaultsFor($role)),
        ];
    }
}
