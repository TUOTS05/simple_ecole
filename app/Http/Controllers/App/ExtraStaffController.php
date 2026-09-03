<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Gestion des comptes du personnel Extras à accès restreint (spec §30) :
 * responsable cantine et responsable transport. Un seul contrôleur paramétré
 * par {type} plutôt que deux copies quasi identiques (AccountantController
 * suit le même besoin mais reste séparé car historiquement antérieur).
 */
class ExtraStaffController extends Controller
{
    private const ROLES = [
        'canteen' => 'canteen_manager',
        'transport' => 'transport_manager',
    ];

    private const LABELS = [
        'canteen' => 'Responsable Cantine',
        'transport' => 'Responsable Transport',
    ];

    private const DESCRIPTIONS = [
        'canteen' => 'Accès limité aux menus et aux présences/consommations des extras.',
        'transport' => 'Accès limité aux véhicules, circuits et affectations de transport.',
    ];

    private function role(string $type): string
    {
        return self::ROLES[$type] ?? abort(404);
    }

    public function index(string $type)
    {
        $role = $this->role($type);
        $schoolId = session('current_school_id');

        $staff = User::where('school_id', $schoolId)
            ->where('role', $role)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);

        return view('app.extra-staff.index', [
            'staff' => $staff,
            'type' => $type,
            'label' => self::LABELS[$type],
            'description' => self::DESCRIPTIONS[$type],
        ]);
    }

    public function create(string $type)
    {
        $this->role($type);

        return view('app.extra-staff.create', [
            'type' => $type,
            'label' => self::LABELS[$type],
            'description' => self::DESCRIPTIONS[$type],
        ]);
    }

    public function store(Request $request, string $type)
    {
        $role = $this->role($type);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
            'gender' => 'required|in:M,F',
        ]);

        $school = School::find(session('current_school_id'));
        if ($school && $school->hasReachedUserLimit()) {
            return back()->withErrors([
                'email' => "Le plafond de {$school->max_users} utilisateurs de votre abonnement est atteint. Contactez le support pour augmenter votre plan.",
            ])->withInput();
        }

        // role et school_id ne sont pas mass-assignables (protection contre l'élévation
        // de privilèges) : on les affecte explicitement après création, comme AccountantController.
        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'],
        ]);
        $user->school_id = session('current_school_id');
        $user->role = $role;
        $user->save();

        return redirect()->route('app.extra-staff.index', $type)
            ->with('success', self::LABELS[$type].' créé avec succès !');
    }

    public function edit(string $type, User $user)
    {
        $role = $this->role($type);

        if ($user->school_id !== session('current_school_id') || $user->role !== $role) {
            abort(403);
        }

        return view('app.extra-staff.edit', [
            'staffUser' => $user,
            'type' => $type,
            'label' => self::LABELS[$type],
        ]);
    }

    public function update(Request $request, string $type, User $user)
    {
        $role = $this->role($type);

        if ($user->school_id !== session('current_school_id') || $user->role !== $role) {
            abort(403);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
            'gender' => 'required|in:M,F',
        ]);

        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->gender = $validated['gender'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('app.extra-staff.index', $type)
            ->with('success', self::LABELS[$type].' mis à jour avec succès !');
    }

    public function destroy(string $type, User $user)
    {
        $role = $this->role($type);

        if ($user->school_id !== session('current_school_id') || $user->role !== $role) {
            abort(403);
        }

        $user->delete();

        return redirect()->route('app.extra-staff.index', $type)
            ->with('success', self::LABELS[$type].' supprimé avec succès !');
    }
}
