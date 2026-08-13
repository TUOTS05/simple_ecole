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

use App\Models\SubscriptionRequest;
use App\Models\Subscription;

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


    /**
     * Liste des demandes en attente
     */
    public function pendingRequests()
    {
        $requests = \App\Models\SubscriptionRequest::with(['school', 'plan'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        return view('superadmin.subscriptions.pending', compact('requests'));
    }

    /**
     * Approuver une demande et générer le contrat
     */
    // public function approveRequest(Request $request, \App\Models\SubscriptionRequest $subRequest)
    // {
    //     $validated = $request->validate([
    //         'admin_notes' => 'nullable|string',
    //     ]);

    //     $school = $subRequest->school;
    //     $plan = $subRequest->plan;

    //     // 1. Marquer la demande comme approuvée
    //     $subRequest->update([
    //         'status' => 'approved',
    //         'admin_notes' => $validated['admin_notes']
    //     ]);

    //     // 2. Réutiliser votre logique existante pour créer le CONTRAT
    //     $contractNumber = 'CTR-' . date('Y') . '-' . strtoupper(\Illuminate\Support\Str::random(6));
    //     $startDate = now();
    //     $endDate = $subRequest->duration === 'yearly' ? now()->addYear() : now()->addMonth();
    //     $amount = $subRequest->duration === 'yearly' ? $plan->yearly_price : $plan->monthly_price;

    //     // Désactiver les anciens contrats actifs
    //     \App\Models\Contract::where('school_id', $school->id)->where('status', 'active')->update(['status' => 'expired']);

    //     $contract = \App\Models\Contract::create([
    //         'school_id' => $school->id,
    //         'contract_number' => $contractNumber,
    //         'plan_name' => $plan->name,
    //         'start_date' => $startDate,
    //         'end_date' => $endDate,
    //         'amount' => $amount,
    //         'max_students' => $plan->max_students,
    //         'max_teachers' => $plan->max_teachers,
    //         'status' => 'active',
    //         'signed_at' => now(),
    //     ]);

    //     // 3. Activer/Mettre à jour l'école
    //     $school->update([
    //         'status' => 'active',
    //         'subscription_plan' => $plan->name,
    //         'subscription_start_date' => $startDate,
    //         'subscription_end_date' => $endDate,
    //         'max_students' => $plan->max_students,
    //         'trial_ends_at' => null, // On arrête l'essai
    //     ]);

    //     // 4. Générer le PDF (votre méthode existante)
    //     $this->generateContractPdf($contract, $school);

    //     // 5. Journaliser
    //     \App\Models\ActivityLog::logAction('approved_subscription', "A approuvé la demande et activé le contrat {$contractNumber} pour {$school->name}");

    //     // TODO: Envoyer un email à l'école avec le contrat en pièce jointe et les coordonnées de paiement

    //     return redirect()->route('superadmin.subscriptions.index')
    //         ->with('success', "✅ Demande approuvée ! Le contrat {$contractNumber} a été généré et l'école est active.");
    // }

    // Dans App\Http\Controllers\SuperAdmin\SubscriptionController.php

    // public function approveRequest(SubscriptionRequest $subRequest, Request $request)
    // {
    //     // 1. Récupérer l'école spécifique liée à CETTE demande
    //     $school = $subRequest->school;
    //     $plan = $subRequest->plan;

    //     $school = $subRequest->school;

    //     // 🛡️ SÉCURITÉ : Empêcher l'activation de l'école de démo
    //     if (str_contains(strtolower($school->name), 'démo') || str_contains(strtolower($school->email ?? ''), 'demo')) {
    //         return redirect()->back()->with('error', '⚠️ Impossible d\'activer un abonnement pour l\'école de démonstration.');
    //     }

    //     // 2. ACTIVER CETTE ÉCOLE (et seulement celle-ci)
    //     $school->update([
    //         'status' => 'active',
    //         'is_active' => true,
    //         'subscription_plan' => $plan->name,
    //         'subscription_start_date' => now(),
    //         'subscription_end_date' => now()->addYear(),
    //     ]);

    //     // 3. Créer le contrat d'abonnement
    //     \App\Models\SubscriptionRequest::create([
    //         'school_id' => $school->id,
    //         'plan_id' => $plan->id,
    //         'plan_name' => $plan->name,
    //         'start_date' => now(),
    //         'end_date' => now()->addYear(),
    //         'amount' => $plan->yearly_price,
    //         'status' => 'active',
    //     ]);

    //     // 4. Mettre à jour la demande
    //     $subRequest->update([
    //         'status' => 'approved',
    //         'admin_notes' => $request->admin_notes,
    //     ]);

    //     // 5. (Optionnel) Envoyer un email au directeur avec ses identifiants

    //     return redirect()->route('superadmin.subscriptions.pending')
    //         ->with('success', "✅ L'école '{$school->name}' a été activée avec succès et le contrat a été généré.");
    // }


        public function approveRequest(SubscriptionRequest $subRequest, Request $request)
    {
        $school = $subRequest->school;
        $plan = $subRequest->plan;

        // 🛡️ SÉCURITÉ : Empêcher l'activation de l'école de démo
        if (str_contains(strtolower($school->name), 'démo') || str_contains(strtolower($school->email ?? ''), 'demo')) {
            return redirect()->back()->with('error', '⚠️ Impossible d\'activer un abonnement pour l\'école de démonstration.');
        }

        // 1. ACTIVER CETTE ÉCOLE
        $school->update([
            'status' => 'active',
            'is_active' => true,
            'subscription_plan' => $plan->name,
            'subscription_start_date' => now(),
            'subscription_end_date' => now()->addYear(),
        ]);

        // 2. ✅ CRÉER LE CONTRAT D'ABONNEMENT (Dans la table 'subscriptions', PAS 'subscription_requests' !)
        \App\Models\Subscription::create([
            'school_id' => $school->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'amount' => $plan->yearly_price,
            'status' => 'active',
        ]);

        // 3. METTRE À JOUR LA DEMANDE EXISTANTE (et non en créer une nouvelle)
        $subRequest->update([
            'status' => 'approved', // ou 'active' selon votre enum, mais 'approved' est logique pour une demande
            'admin_notes' => $request->admin_notes ?? null,
        ]);


        // 🚀 4. ENVOYER LES IDENTIFIANTS AU DIRECTEUR
        $director = \App\Models\User::where('school_id', $school->id)
                                    ->where('role', 'school_admin')
                                    ->first();
        
        if ($director) {
            \Illuminate\Support\Facades\Mail::to($director->email)->send(
                new \App\Mail\SchoolAdminWelcomeMail($school->name, $director->email, 'Temporaire123!')
            );
        }

        return redirect()->route('superadmin.subscriptions.pending')
            ->with('success', "✅ L'école '{$school->name}' a été activée avec succès et le contrat a été généré.");
    }

    /**
     * Refuser une demande
     */
    public function rejectRequest(Request $request, \App\Models\SubscriptionRequest $subRequest)
    {
        $validated = $request->validate([
            'admin_notes' => 'required|string', // Raison du refus
        ]);

        $subRequest->update([
            'status' => 'rejected',
            'admin_notes' => $validated['admin_notes']
        ]);

        return redirect()->route('superadmin.subscriptions.pending')
            ->with('success', "❌ Demande refusée. L'administrateur de l'école sera notifié.");
    }
}
