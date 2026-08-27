<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubscriptionPlanController extends Controller
{
    /**
     * Afficher la liste des plans
     */
    public function index()
    {
        $plans = SubscriptionPlan::orderBy('sort_order')->orderBy('name')->paginate(15);

        return view('superadmin.plans.index', compact('plans'));
    }

    /**
     * Formulaire de création
     */
    public function create()
    {
        return view('superadmin.plans.create');
    }

    /**
     * Sauvegarder un nouveau plan
     */
    public function store(Request $request)
    {
        // 1. Validation (Notez que 'is_active' et 'sort_order' sont gérés après)
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:subscription_plans,slug',
            'description' => 'nullable|string',
            'max_students' => 'required|integer|min:1',
            'max_teachers' => 'required|integer|min:1',
            'max_classes' => 'required|integer|min:1',
            'monthly_price' => 'required|numeric|min:0',
        ]);

        // 2. Traitement des champs spéciaux
        // Générer le slug si vide
        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);

        // Gérer la checkbox : true si cochée, false sinon
        $validated['is_active'] = $request->has('is_active');

        // Donner une valeur par défaut à sort_order s'il est vide
        $validated['sort_order'] = $request->input('sort_order', 0);

        // 3. Création
        SubscriptionPlan::create($validated);

        return redirect()->route('superadmin.plans.index')
            ->with('success', '✅ Plan d\'abonnement créé avec succès !');
    }

    /**
     * Formulaire de modification
     */
    public function edit(SubscriptionPlan $plan)
    {
        return view('superadmin.plans.edit', compact('plan'));
    }

    /**
     * Mettre à jour un plan
     */
    public function update(Request $request, SubscriptionPlan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:subscription_plans,slug,'.$plan->id,
            'description' => 'nullable|string',
            'max_students' => 'required|integer|min:1',
            'max_teachers' => 'required|integer|min:1',
            'max_classes' => 'required|integer|min:1',
            'monthly_price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $plan->update($validated);

        return redirect()->route('superadmin.plans.index')
            ->with('success', '✅ Plan mis à jour avec succès !');
    }

    /**
     * Supprimer un plan
     */
    public function destroy(SubscriptionPlan $plan)
    {
        // Vérifier qu'aucune école n'utilise ce plan
        $schoolsCount = School::where('subscription_plan', $plan->slug)->count();

        if ($schoolsCount > 0) {
            return redirect()->route('superadmin.plans.index')
                ->with('error', '❌ Impossible de supprimer : '.$schoolsCount.' école(s) utilisent encore ce plan.');
        }

        $plan->delete();

        return redirect()->route('superadmin.plans.index')
            ->with('success', '✅ Plan supprimé avec succès !');
    }
}
