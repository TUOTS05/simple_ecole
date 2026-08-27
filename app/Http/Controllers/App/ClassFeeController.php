<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class ClassFeeController extends Controller
{
    /**
     * Afficher la liste des classes avec leurs frais
     */
    public function index()
    {
        $schoolId = session('current_school_id');

        $classes = SchoolClass::where('school_id', $schoolId)
            ->orderBy('name')
            ->get();

        return view('app.class-fees.index', compact('classes'));
    }

    /**
     * Afficher le formulaire d'édition pour une classe
     */
    public function edit(SchoolClass $schoolClass)
    {
        // Vérification de sécurité MVC
        if ($schoolClass->school_id !== session('current_school_id')) {
            abort(403, 'Accès non autorisé.');
        }

        return view('app.class-fees.edit', compact('schoolClass'));
    }

    /**
     * Mettre à jour les frais d'une classe
     */
    public function update(Request $request, SchoolClass $schoolClass)
    {
        // Vérification de sécurité MVC
        if ($schoolClass->school_id !== session('current_school_id')) {
            abort(403, 'Accès non autorisé.');
        }

        $validated = $request->validate([
            'total_tuition' => 'required|numeric|min:0',
            'registration_fee' => 'required|numeric|min:0|lte:total_tuition',
            'payment_modality' => 'required|in:unique,mensuel,trimestriel,semestriel',
            'number_of_installments' => 'required|integer|min:1|max:12',
        ]);

        // Calcul automatique du montant par échéance (Logique Métier)
        $remainingAmount = $validated['total_tuition'] - $validated['registration_fee'];

        if ($validated['payment_modality'] === 'unique') {
            $validated['installment_amount'] = $remainingAmount;
            $validated['number_of_installments'] = 1;
        } else {
            $validated['installment_amount'] = $remainingAmount / $validated['number_of_installments'];
        }

        // Mise à jour du Modèle
        $schoolClass->update($validated);

        return redirect()->route('app.class-fees.index')
            ->with('success', 'Configuration des frais mise à jour avec succès !');
    }
}
