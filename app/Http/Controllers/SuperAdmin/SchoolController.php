<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Super-administration : gestion des écoles clientes.
 */
class SchoolController extends Controller
{
    /**
     * Affiche la liste.
     */
    public function index(Request $request)
    {
        $query = School::query();

        // Filtre par statut (inclut la logique d'expiration automatique)
        if ($request->filled('status')) {
            if ($request->status === 'expired') {
                $query->where('status', 'expired')
                    ->orWhereDate('subscription_end_date', '<', Carbon::today());
            } else {
                $query->where('status', $request->status);
            }
        }

        // Filtre par type d'école
        if ($request->filled('school_type')) {
            $query->where('school_type', $request->school_type);
        }

        // Recherche par nom
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $schools = $query->orderBy('created_at', 'desc')->paginate(15);
        $schools->appends($request->query());

        return view('superadmin.schools.index', compact('schools'));
    }

    /**
     * Affiche le formulaire de création.
     */
    public function create()
    {
        return view('superadmin.schools.create');
    }

    /**
     * Enregistre un nouvel élément.
     */

    /**
     * Crée une école (slug unique, logo facultatif) et son administrateur ; l'école reste en attente
     * d'abonnement.
     */
    public function store(Request $request)
    {
        // 1. Validation des données du formulaire
        $validated = $request->validate([
            // Infos de l'école
            'name' => 'required|string|max:255',
            'school_type' => 'required|in:maternelle,primaire,both,creche',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',

            // Infos du directeur (Admin de l'école)
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255|unique:users,email',
            'admin_password' => 'required|string|min:8',
        ]);

        // 2. Préparation des données de l'école
        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $counter = 1;

        // Garantir l'unicité du slug
        while (School::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        $schoolData = [
            'name' => $validated['name'],
            'slug' => $slug,
            'school_type' => $validated['school_type'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'status' => 'suspended', // FORCÉ À SUSPENDED (En attente d'abonnement)
        ];

        // Gérer l'upload du logo
        if ($request->hasFile('logo')) {
            $schoolData['logo'] = $request->file('logo')->store('schools/logos', 'public');
        }

        // 3. Création de l'école
        $school = School::create($schoolData);

        // 4. Création automatique du compte Directeur/Admin de l'école
        // On découpe le nom complet en Prénom et Nom (si possible) pour la BDD
        $nameParts = explode(' ', $validated['admin_name'], 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? $firstName;

        // role et school_id ne sont pas mass-assignables (protection contre l'élévation
        // de privilèges) : on les affecte explicitement après création.
        $schoolAdmin = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $validated['admin_name'],
            'email' => $validated['admin_email'],
            'password' => Hash::make($validated['admin_password']),
        ]);
        $schoolAdmin->role = 'school_admin';
        $schoolAdmin->school_id = $school->id;
        $schoolAdmin->save();

        // 5. Journaliser l'action
        ActivityLog::logAction(
            'created_school',
            "A créé l'école '{$school->name}' avec l'admin {$validated['admin_email']}"
        );

        // 6. Redirection avec message de succès
        $successMessage = "✅ École '{$school->name}' créée avec succès !\n\n"
            ."⏳ Statut : En attente d'abonnement.\n"
            ."👤 Le directeur peut désormais se connecter avec l'email : {$validated['admin_email']}\n"
            ."⚠️ N'oubliez pas de lui attribuer un abonnement sur la page 'Abonnements' pour activer l'école.";

        return redirect()->route('superadmin.schools.index')
            ->with('success', $successMessage);
    }

    /**
     * Affiche le détail d'un élément.
     */
    public function show(School $school)
    {
        $school->loadCount(['users', 'students', 'classes']);

        return view('superadmin.schools.show', compact('school'));
    }

    /**
     * Affiche le formulaire de modification.
     */
    public function edit(School $school)
    {
        return view('superadmin.schools.edit', compact('school'));
    }

    /**
     * Met à jour un élément.
     */
    public function update(Request $request, School $school)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:schools,slug,'.$school->id,
            'school_type' => 'required|in:maternelle,primaire,both,creche',
            'status' => 'required|in:active,suspended,expired', // UNIFORMISÉ (suppression de 'trial')
            'subscription_plan' => 'required|in:basic,premium,enterprise', // AJOUTÉ POUR SAAS
            'subscription_start_date' => 'required|date', // AJOUTÉ POUR SAAS
            'subscription_end_date' => 'required|date', // AJOUTÉ POUR SAAS
            'max_students' => 'required|integer|min:1', // AJOUTÉ POUR SAAS
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // LOGIQUE MÉTIER : Si la date de fin est dans le passé, forcer le statut à 'expired'
        if (Carbon::parse($validated['subscription_end_date'])->isPast()) {
            $validated['status'] = 'expired';
        }

        // Gérer l'upload du nouveau logo
        if ($request->hasFile('logo')) {
            if ($school->logo) {
                Storage::disk('public')->delete($school->logo);
            }
            $validated['logo'] = $request->file('logo')->store('schools/logos', 'public');
        }

        $school->update($validated);

        ActivityLog::logAction(
            'updated_school',
            "A modifié l'école '{$school->name}'"
        );

        return redirect()->route('superadmin.schools.index')
            ->with('success', '✅ École et abonnement mis à jour avec succès !');
    }

    /**
     * Supprime un élément.
     */

    /**
     * Supprime un élément (suppression logique, récupérable).
     */
    public function destroy(School $school)
    {
        // Vérification de sécurité : si déjà supprimée, on ne fait rien
        if ($school->trashed()) {
            return redirect()->route('superadmin.schools.index')
                ->with('error', '❌ Cette école est déjà archivée.');
        }

        // La suppression douce : met à jour 'deleted_at' au lieu de supprimer la ligne
        // Les utilisateurs et élèves liés restent intacts en base de données
        $school->delete();

        ActivityLog::logAction(
            'deleted_school',
            "A archivé l'école '{$school->name}'"
        );

        return redirect()->route('superadmin.schools.index')
            ->with('success', '✅ École archivée avec succès ! (Les données sont conservées en sécurité)');
    }
}
