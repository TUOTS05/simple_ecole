<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\ActivityLog;

class SchoolController extends Controller
{
    /**
     * Display a listing of the resource.
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
            $query->where('name', 'like', '%' . $request->search . '%');
        }
                
        $schools = $query->orderBy('created_at', 'desc')->paginate(15);
        $schools->appends($request->query());
        
        return view('superadmin.schools.index', compact('schools'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('superadmin.schools.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|email|max:255',
    //         'phone' => 'required|number|max:10',
    //         'address' => 'required|string',
    //         'school_type' => 'required|in:maternelle,primaire,both', // ✅ AJOUTÉ
    //         'status' => 'required|in:active,suspended,expired', // ✅ UNIFORMISÉ
    //         'subscription_plan' => 'required|in:basic,premium,enterprise',
    //         'subscription_start_date' => 'required|date',
    //         'subscription_end_date' => 'required|date|after_or_equal:subscription_start_date',
    //         'max_students' => 'required|integer|min:1',
    //         'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    //     ]);

    //             // ✅ VALIDATION MÉTIER : Une école active doit avoir un abonnement valide
    //     if ($validated['status'] === 'active') {
    //         if (empty($validated['subscription_plan'])) {
    //             return back()->withErrors(['subscription_plan' => 'Une école active doit avoir un plan d\'abonnement.']);
    //         }
    //         if (empty($validated['subscription_start_date']) || empty($validated['subscription_end_date'])) {
    //             return back()->withErrors(['subscription_end_date' => 'Une école active doit avoir des dates d\'abonnement.']);
    //         }
    //     }
        
    //     // ✅ CORRECTION : Générer le slug de manière fiable
    //     $validated['slug'] = Str::slug($validated['name']);
        
    //     // Gérer l'upload du logo
    //     if ($request->hasFile('logo')) {
    //         $validated['logo'] = $request->file('logo')->store('schools/logos', 'public');
    //     }
        
    //     School::create($validated);
        
    //     return redirect()->route('superadmin.schools.index')
    //         ->with('success', '✅ École créée avec succès !');
    // }

    //     public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|email|max:255',
    //         'phone' => 'nullable|string|max:50',
    //         'address' => 'nullable|string',
    //         'school_type' => 'required|in:maternelle,primaire,both',
    //         'status' => 'required|in:active,suspended,expired',
    //         'subscription_plan' => 'required|in:basic,premium,enterprise',
    //         'subscription_start_date' => 'required|date',
    //         'subscription_end_date' => 'required|date|after_or_equal:subscription_start_date',
    //         'max_students' => 'required|integer|min:1',
    //         'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    //     ]);

    //     // ✅ VALIDATION MÉTIER : Une école active doit avoir un abonnement valide
    //     if ($validated['status'] === 'active') {
    //         if (empty($validated['subscription_plan'])) {
    //             return back()->withErrors(['subscription_plan' => 'Une école active doit avoir un plan d\'abonnement.']);
    //         }
    //         if (empty($validated['subscription_start_date']) || empty($validated['subscription_end_date'])) {
    //             return back()->withErrors(['subscription_end_date' => 'Une école active doit avoir des dates d\'abonnement.']);
    //         }
    //     }

    //     $validated['slug'] = Str::slug($validated['name']);
        
    //     if ($request->hasFile('logo')) {
    //         $validated['logo'] = $request->file('logo')->store('schools/logos', 'public');
    //     }
        
    //     School::create($validated);
        
    //     return redirect()->route('superadmin.schools.index')
    //         ->with('success', '✅ École créée avec succès !');
    // }


    //     public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|email|max:255|unique:users,email', // Doit être unique car ce sera le login
    //         'phone' => 'nullable|string|max:50',
    //         'address' => 'nullable|string',
    //         'school_type' => 'required|in:maternelle,primaire,both',
    //         'subscription_plan' => 'required|string',
    //         'subscription_start_date' => 'required|date',
    //         'subscription_end_date' => 'required|date|after_or_equal:subscription_start_date',
    //         'max_students' => 'required|integer|min:1',
    //         'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    //     ]);

    //     // 1. Génération du token de validation sécurisé
    //     $validationToken = \Illuminate\Support\Str::random(32);

    //             // 2. Préparation des données de l'école (Statut forcé à 'suspended' en attendant la validation)
    //     $baseSlug = \Illuminate\Support\Str::slug($validated['name']);
    //     $slug = $baseSlug;
    //     $counter = 1;

    //     // Boucle pour garantir l'unicité du slug (ex: groupe-scolaire-chigata, groupe-scolaire-chigata-1, etc.)
    //     while (\App\Models\School::where('slug', $slug)->exists()) {
    //         $slug = $baseSlug . '-' . $counter;
    //         $counter++;
    //     }

    //     $schoolData = array_merge($validated, [
    //         'status' => 'suspended', 
    //         'validation_token' => $validationToken,
    //         'slug' => $slug,
    //     ]);
        
    //     if ($request->hasFile('logo')) {
    //         $schoolData['logo'] = $request->file('logo')->store('schools/logos', 'public');
    //     }
        
    //     // 3. Création de l'école
    //     $school = \App\Models\School::create($schoolData);
        
    //             // 4. Création automatique du compte Admin de l'école
    //     $adminPassword = \Illuminate\Support\Str::random(10); // Mot de passe aléatoire de 10 caractères
        
    //     \App\Models\User::create([
    //         'first_name' => 'Administrateur',
    //         'last_name' => $validated['name'], // Le nom de l'école sert de nom de famille pour l'admin par défaut
    //         'name' => 'Administrateur ' . $validated['name'], // Gardé au cas où votre modèle l'utilise aussi
    //         'email' => $validated['email'],
    //         'password' => \Illuminate\Support\Facades\Hash::make($adminPassword),
    //         'role' => 'school_admin',
    //         'school_id' => $school->id,
    //     ]);

    //     // 5. Envoi de l'email de bienvenue et de validation du contrat
    //     // (Nous créerons la classe d'email à l'étape 2.2)
    //     try {
    //         \Illuminate\Support\Facades\Mail::to($validated['email'])->send(
    //             new \App\Mail\SchoolWelcomeMail($school, $adminPassword)
    //         );
    //     } catch (\Exception $e) {
    //         // En cas d'échec d'envoi, on log l'erreur mais on ne bloque pas la création
    //         \Illuminate\Support\Facades\Log::error('Échec envoi email welcome school: ' . $e->getMessage());
    //     }

    //     // 6. Redirection avec les identifiants affichés au Super Admin
    //     return redirect()->route('superadmin.schools.index')
    //         ->with('success', "✅ École créée avec succès !\n\n👤 Email Admin : {$validated['email']}\n🔑 Mot de passe : {$adminPassword}\n\n⚠️ Un email de validation a été envoyé. L'école sera activée après validation du contrat.");
    // }

    public function store(Request $request)
    {
        // 1. Validation des données du formulaire
        $validated = $request->validate([
            // Infos de l'école
            'name' => 'required|string|max:255',
            'school_type' => 'required|in:maternelle,primaire,both',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|number|max:10',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            
            // Infos du directeur (Admin de l'école)
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255|unique:users,email',
            'admin_password' => 'required|string|min:8',
        ]);

        // 2. Préparation des données de l'école
        $baseSlug = \Illuminate\Support\Str::slug($validated['name']);
        $slug = $baseSlug;
        $counter = 1;

        // Garantir l'unicité du slug
        while (\App\Models\School::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $schoolData = [
            'name' => $validated['name'],
            'slug' => $slug,
            'school_type' => $validated['school_type'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'status' => 'suspended', // ✅ FORCÉ À SUSPENDED (En attente d'abonnement)
        ];
        
        // Gérer l'upload du logo
        if ($request->hasFile('logo')) {
            $schoolData['logo'] = $request->file('logo')->store('schools/logos', 'public');
        }
        
        // 3. Création de l'école
        $school = \App\Models\School::create($schoolData);
        
        // 4. Création automatique du compte Directeur/Admin de l'école
        // On découpe le nom complet en Prénom et Nom (si possible) pour la BDD
        $nameParts = explode(' ', $validated['admin_name'], 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? $firstName; 

        \App\Models\User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $validated['admin_name'],
            'email' => $validated['admin_email'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['admin_password']),
            'role' => 'school_admin',
            'school_id' => $school->id,
        ]);

                // 5. Journaliser l'action
        ActivityLog::logAction(
            'created_school', 
            "A créé l'école '{$school->name}' avec l'admin {$validated['admin_email']}"
        );

        return redirect()->route('superadmin.schools.index')
            ->with('success', $successMessage);

        // 5. Redirection avec message de succès
        $successMessage = "✅ École '{$school->name}' créée avec succès !\n\n"
            . "⏳ Statut : En attente d'abonnement.\n"
            . "👤 Le directeur peut désormais se connecter avec l'email : {$validated['admin_email']}\n"
            . "⚠️ N'oubliez pas de lui attribuer un abonnement sur la page 'Abonnements' pour activer l'école.";

        return redirect()->route('superadmin.schools.index')
            ->with('success', $successMessage);
    }

    /**
     * Display the specified resource.
     */
    public function show(School $school)
    {
        $school->loadCount(['users', 'students', 'classes']);
        return view('superadmin.schools.show', compact('school'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(School $school)
    {
        return view('superadmin.schools.edit', compact('school'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, School $school)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:schools,slug,' . $school->id,
            'school_type' => 'required|in:maternelle,primaire,both',
            'status' => 'required|in:active,suspended,expired', // ✅ UNIFORMISÉ (suppression de 'trial')
            'subscription_plan' => 'required|in:basic,premium,enterprise', // ✅ AJOUTÉ POUR SAAS
            'subscription_start_date' => 'required|date', // ✅ AJOUTÉ POUR SAAS
            'subscription_end_date' => 'required|date', // ✅ AJOUTÉ POUR SAAS
            'max_students' => 'required|integer|min:1', // ✅ AJOUTÉ POUR SAAS
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);
        
        // ✅ LOGIQUE MÉTIER : Si la date de fin est dans le passé, forcer le statut à 'expired'
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
        
        return redirect()->route('superadmin.schools.index')
            ->with('success', '✅ École et abonnement mis à jour avec succès !');
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(School $school)
    // {
    //     // Vérifier que l'école n'a pas d'utilisateurs
    //     if ($school->users()->count() > 0) {
    //         return redirect()->route('superadmin.schools.index')
    //             ->with('error', '❌ Impossible de supprimer : cette école possède des utilisateurs.');
    //     }
        
    //     // Vérifier que l'école n'a pas d'élèves
    //     if ($school->students()->count() > 0) {
    //         return redirect()->route('superadmin.schools.index')
    //             ->with('error', '❌ Impossible de supprimer : cette école possède des élèves.');
    //     }
        
    //     // Supprimer le logo s'il existe
    //     if ($school->logo) {
    //         Storage::disk('public')->delete($school->logo);
    //     }
        
    //     $school->delete();
        
    //     return redirect()->route('superadmin.schools.index')
    //         ->with('success', '✅ École supprimée avec succès !');
    // }

        /**
     * Remove the specified resource from storage (Soft Delete).
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

        return redirect()->route('superadmin.schools.index')
            ->with('success', '✅ École archivée avec succès ! (Les données sont conservées en sécurité)');
    }
}