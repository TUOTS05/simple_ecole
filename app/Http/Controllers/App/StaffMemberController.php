<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\StaffFunction;
use App\Models\User;
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
        $staff = User::where('school_id', session('current_school_id'))
            ->where('role', 'staff')
            ->with('staffFunction')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);

        return view('app.staff.index', compact('staff'));
    }

    /**
     * Formulaire de création d'un compte du personnel.
     */
    public function create()
    {
        return view('app.staff.form', [
            'member' => new User,
            'functions' => $this->functions(),
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
        $member = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'],
        ]);
        $member->school_id = session('current_school_id');
        $member->role = 'staff';
        $member->staff_function_id = $validated['staff_function_id'];
        $member->save();

        return redirect()->route('app.staff.index')
            ->with('success', "Compte de {$member->full_name} créé.");
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
        $member->staff_function_id = $validated['staff_function_id'];

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

    private function functions()
    {
        return StaffFunction::where('school_id', session('current_school_id'))->orderBy('name')->get();
    }

    /**
     * Valide le formulaire ; la fonction choisie doit appartenir à l'école.
     */
    private function validated(Request $request, ?User $member = null): array
    {
        return $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($member?->id)],
            'password' => [$member ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
            'phone' => 'nullable|string|max:20',
            'gender' => 'required|in:M,F',
            // Uniquement une fonction de l'école courante
            'staff_function_id' => [
                'required',
                Rule::exists('staff_functions', 'id')->where('school_id', session('current_school_id')),
            ],
        ]);
    }

    private function authorizeMember(User $member): void
    {
        abort_if($member->school_id !== session('current_school_id') || ! $member->isStaff(), 403);
    }
}
