<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::query()->with('school');
        
        // Filtre par rôle
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        
        // Filtre par école
        if ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }
        
        // Recherche par nom ou email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', '%' . $search . '%')
                  ->orWhere('last_name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }
        
        $users = $query->orderBy('created_at', 'desc')->paginate(15);
        $users->appends($request->query());
        
        // Récupérer les écoles pour le filtre
        $schools = School::orderBy('name')->get();
        
        return view('superadmin.users.index', compact('users', 'schools'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $schools = School::orderBy('name')->get();
        
        return view('superadmin.users.create', compact('schools'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:super_admin,school_admin,teacher,parent',
            'school_id' => 'nullable|exists:schools,id',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        
        // Validation conditionnelle : school_id obligatoire sauf pour super_admin
        if ($validated['role'] !== 'super_admin' && empty($validated['school_id'])) {
            return back()->withErrors(['school_id' => 'Une école doit être sélectionnée pour ce rôle.'])
                        ->withInput();
        }
        
        // Si super_admin, on met school_id à null
        if ($validated['role'] === 'super_admin') {
            $validated['school_id'] = null;
        }
        
        // Hasher le mot de passe
        $validated['password'] = Hash::make($validated['password']);
        
        // Supprimer password_confirmation
        unset($validated['password_confirmation']);
        
        User::create($validated);
        
        return redirect()->route('superadmin.users.index')
            ->with('success', 'Utilisateur créé avec succès !');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $user->load('school');
        
        return view('superadmin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $schools = School::orderBy('name')->get();
        
        return view('superadmin.users.edit', compact('user', 'schools'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:super_admin,school_admin,teacher,parent',
            'school_id' => 'nullable|exists:schools,id',
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);
        
        // Validation conditionnelle : school_id obligatoire sauf pour super_admin
        if ($validated['role'] !== 'super_admin' && empty($validated['school_id'])) {
            return back()->withErrors(['school_id' => 'Une école doit être sélectionnée pour ce rôle.'])
                        ->withInput();
        }
        
        // Si super_admin, on met school_id à null
        if ($validated['role'] === 'super_admin') {
            $validated['school_id'] = null;
        }
        
        // Hasher le mot de passe seulement si fourni
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        
        // Supprimer password_confirmation
        unset($validated['password_confirmation']);
        
        $user->update($validated);
        
        return redirect()->route('superadmin.users.index')
            ->with('success', 'Utilisateur mis à jour avec succès !');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Empêcher la suppression du dernier super_admin
        if ($user->isSuperAdmin() && User::where('role', 'super_admin')->count() === 1) {
            return redirect()->route('superadmin.users.index')
                ->with('error', 'Impossible de supprimer le dernier super administrateur.');
        }
        
        $user->delete();
        
        return redirect()->route('superadmin.users.index')
            ->with('success', 'Utilisateur supprimé avec succès !');
    }
}