<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Accountant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountantController extends Controller
{
    public function index()
    {
        $schoolId = session('current_school_id');

        $accountants = Accountant::where('school_id', $schoolId)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);

        return view('app.accountants.index', compact('accountants'));
    }

    public function create()
    {
        return view('app.accountants.create');
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

        // role et school_id ne sont pas mass-assignables (protection contre l'élévation
        // de privilèges) : on les affecte explicitement après création.
        $accountant = Accountant::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'],
        ]);
        $accountant->school_id = session('current_school_id');
        $accountant->role = 'accountant';
        $accountant->save();

        return redirect()->route('app.accountants.index')
            ->with('success', 'Personnel comptable créé avec succès !');
    }

    public function edit(Accountant $accountant)
    {
        if ($accountant->school_id !== session('current_school_id')) {
            abort(403);
        }

        return view('app.accountants.edit', compact('accountant'));
    }

    public function update(Request $request, Accountant $accountant)
    {
        if ($accountant->school_id !== session('current_school_id')) {
            abort(403);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $accountant->id,
            'password' => 'nullable|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
            'gender' => 'required|in:M,F',
        ]);

        $accountant->first_name = $validated['first_name'];
        $accountant->last_name = $validated['last_name'];
        $accountant->email = $validated['email'];
        $accountant->phone = $validated['phone'] ?? null;
        $accountant->gender = $validated['gender'];

        if (!empty($validated['password'])) {
            $accountant->password = Hash::make($validated['password']);
        }

        $accountant->save();

        return redirect()->route('app.accountants.index')
            ->with('success', 'Personnel comptable mis à jour avec succès !');
    }

    public function destroy(Accountant $accountant)
    {
        if ($accountant->school_id !== session('current_school_id')) {
            abort(403);
        }

        $accountant->delete();

        return redirect()->route('app.accountants.index')
            ->with('success', 'Personnel comptable supprimé avec succès !');
    }
}
