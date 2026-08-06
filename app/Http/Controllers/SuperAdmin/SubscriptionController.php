<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SubscriptionPlan;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ActivityLog;


class SubscriptionController extends Controller
{
    public function index()
    {
        $schools = School::orderBy('name')->get();
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('yearly_price')->get();
        $contracts = Contract::with('school')->latest()->get();

        return view('superadmin.subscriptions.index', compact('schools', 'plans', 'contracts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'plan_id' => 'required|exists:subscription_plans,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'amount' => 'required|numeric|min:0',
        ]);

        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $school = School::findOrFail($validated['school_id']);

        // 1. Générer un numéro de contrat unique
        $contractNumber = 'CTR-' . date('Y') . '-' . strtoupper(Str::random(6));

                $school = School::findOrFail($validated['school_id']);

        // ✅ RÈGLE MÉTIER : Désactiver tout contrat "actif" existant pour cette école
        Contract::where('school_id', $school->id)
                ->where('status', 'active')
                ->update(['status' => 'expired']);

        // 2. Créer le contrat
        $contract = Contract::create([
            'school_id' => $validated['school_id'],
            'contract_number' => $contractNumber,
            'plan_name' => $plan->name,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'amount' => $validated['amount'],
            'max_students' => $plan->max_students ?? 0,
            'max_teachers' => $plan->max_teachers ?? 0,
            'status' => 'active',
            'signed_at' => now(),
        ]);

        // 3. Activer l'école
        $school->update([
            'status' => 'active',
            'subscription_plan' => $plan->name,
            'subscription_start_date' => $validated['start_date'],
            'subscription_end_date' => $validated['end_date'],
            'max_students' => $plan->max_students ?? 999999,
        ]);

        // 4. Générer le PDF du contrat
        $this->generateContractPdf($contract, $school);
        // Dans store() :
        ActivityLog::logAction('created_contract', "A activé le contrat {$contractNumber} pour l'école {$school->name}");


        return redirect()->route('superadmin.subscriptions.index')
            ->with('success', "✅ Contrat {$contractNumber} créé pour {$school->name} ! L'école est active et le PDF est prêt.");
    }

    /**
     * Générer et sauvegarder le PDF du contrat
     */
    private function generateContractPdf(Contract $contract, School $school)
    {
        // Générer le PDF à partir de la vue Blade
        $pdf = Pdf::loadView('pdf.contract', compact('contract', 'school'));
        
        $fileName = 'contrat_' . $contract->contract_number . '.pdf';
        $filePath = 'contracts/' . $fileName;
        
        // Sauvegarder le fichier dans storage/app/public/contracts/
        Storage::disk('public')->put($filePath, $pdf->output());

        // Mettre à jour le contrat avec le chemin du fichier
        $contract->update(['pdf_path' => $filePath]);
    }

        /**
     * Afficher le formulaire de renouvellement
     */
    // public function renew(Contract $contract)
    // {
    //     // On pré-remplit les dates : début = lendemain de l'ancien, fin = +1 an
    //     $newStartDate = \Carbon\Carbon::parse($contract->end_date)->addDay()->format('Y-m-d');
    //     $newEndDate = \Carbon\Carbon::parse($newStartDate)->addYear()->format('Y-m-d');

    //     return view('superadmin.subscriptions.renew', compact('contract', 'newStartDate', 'newEndDate'));
    // }

    //     public function renew($id)
    // {
    //     // Force la récupération du contrat, même s'il est soft-deleted
    //     $contract = \App\Models\Contract::withTrashed()->findOrFail($id);
        
    //     $newStartDate = \Carbon\Carbon::parse($contract->end_date)->addDay()->format('Y-m-d');
    //     $newEndDate = \Carbon\Carbon::parse($newStartDate)->addYear()->format('Y-m-d');

    //     return view('superadmin.subscriptions.renew', compact('contract', 'newStartDate', 'newEndDate'));
    // }

    /**
     * Traiter le renouvellement (Créer un NOUVEAU contrat)
     */
    //       public function storeRenewal(Request $request, Contract $oldContract)
    // {
    //     $validated = $request->validate([
    //         'start_date' => 'required|date',
    //         'end_date' => 'required|date|after:start_date',
    //         'amount' => 'required|numeric|min:0',
    //     ]);

    //     // ✅ CORRECTION : Récupérer l'école directement par son ID pour éviter le "null"
    //     $school = School::findOrFail($oldContract->school_id);
    //     $planName = $oldContract->plan_name;

    //     // ✅ RÈGLE MÉTIER 1 : Marquer l'ancien contrat comme "renouvelé"
    //     $oldContract->update(['status' => 'renewed']);

    //     // ✅ RÈGLE MÉTIER 2 : Fermer tout autre contrat "actif" pour cette école (sécurité absolue)
    //     Contract::where('school_id', $school->id)
    //             ->where('id', '!=', $oldContract->id)
    //             ->where('status', 'active')
    //             ->update(['status' => 'expired']);

    //     // 1. Générer un nouveau numéro de contrat unique
    //     $newContractNumber = 'CTR-' . date('Y') . '-' . strtoupper(\Illuminate\Support\Str::random(6));

    //     // 2. Créer le NOUVEAU contrat (qui devient le seul "actif")
    //     $newContract = Contract::create([
    //         'school_id' => $school->id,
    //         'contract_number' => $newContractNumber,
    //         'plan_name' => $planName,
    //         'start_date' => $validated['start_date'],
    //         'end_date' => $validated['end_date'],
    //         'amount' => $validated['amount'],
    //         'max_students' => $oldContract->max_students,
    //         'max_teachers' => $oldContract->max_teachers,
    //         'status' => 'active',
    //         'signed_at' => now(),
    //     ]);

    //     // 3. Mettre à jour l'école (la réactiver et mettre à jour la date de fin)
    //     $school->update([
    //         'status' => 'active',
    //         'subscription_end_date' => $validated['end_date'],
    //     ]);

    //     // 4. Générer le nouveau PDF
    //     $this->generateContractPdf($newContract, $school);

    //     // 5. Journaliser l'action (si vous avez mis en place les Activity Logs)
    //     // ActivityLog::logAction('renewed_contract', "A renouvelé le contrat de l'école {$school->name} (Nouveau: {$newContractNumber})");

    //     return redirect()->route('superadmin.subscriptions.index')
    //         ->with('success', "✅ Contrat renouvelé ! Nouveau contrat {$newContractNumber} généré pour {$school->name}.");
    // }

    //     public function storeRenewal(Request $request, $id)
    // {
    //     // Force la récupération du contrat
    //     $oldContract = \App\Models\Contract::withTrashed()->findOrFail($id);
        
    //     $validated = $request->validate([
    //         'start_date' => 'required|date',
    //         'end_date' => 'required|date|after:start_date',
    //         'amount' => 'required|numeric|min:0',
    //     ]);

    //     $school = \App\Models\School::findOrFail($oldContract->school_id);
    //     $planName = $oldContract->plan_name;

    //     // ... (le reste de votre méthode storeRenewal reste inchangé)
    // }


        public function renew($id)
    {
        // Récupération standard du contrat
        $contract = \App\Models\Contract::findOrFail($id);
        
        $newStartDate = \Carbon\Carbon::parse($contract->end_date)->addDay()->format('Y-m-d');
        $newEndDate = \Carbon\Carbon::parse($newStartDate)->addYear()->format('Y-m-d');

        return view('superadmin.subscriptions.renew', compact('contract', 'newStartDate', 'newEndDate'));
    }

    public function storeRenewal(Request $request, $id)
    {
        // Récupération standard du contrat
        $oldContract = \App\Models\Contract::findOrFail($id);
        
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'amount' => 'required|numeric|min:0',
        ]);

        $school = \App\Models\School::findOrFail($oldContract->school_id);
        $planName = $oldContract->plan_name;

        // 1. Marquer l'ancien contrat comme renouvelé
        $oldContract->update(['status' => 'renewed']);

        // 2. Fermer tout autre contrat actif pour cette école
        \App\Models\Contract::where('school_id', $school->id)
            ->where('id', '!=', $oldContract->id)
            ->where('status', 'active')
            ->update(['status' => 'expired']);

        // 3. Générer un nouveau numéro de contrat
        $newContractNumber = 'CTR-' . date('Y') . '-' . strtoupper(\Illuminate\Support\Str::random(6));

        // 4. Créer le NOUVEAU contrat
        $newContract = \App\Models\Contract::create([
            'school_id' => $school->id,
            'contract_number' => $newContractNumber,
            'plan_name' => $planName,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'amount' => $validated['amount'],
            'max_students' => $oldContract->max_students,
            'max_teachers' => $oldContract->max_teachers,
            'status' => 'active',
            'signed_at' => now(),
        ]);

        // 5. Mettre à jour l'école
        $school->update([
            'status' => 'active',
            'subscription_end_date' => $validated['end_date'],
        ]);

        // 6. Générer le nouveau PDF
        $this->generateContractPdf($newContract, $school);

        return redirect()->route('superadmin.subscriptions.index')
            ->with('success', "✅ Contrat renouvelé ! Nouveau contrat {$newContractNumber} généré pour {$school->name}.");
    }
}