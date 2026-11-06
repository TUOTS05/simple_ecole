<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Mail\SchoolAdminWelcomeMail;
use App\Models\ActivityLog;
use App\Models\Contract;
use App\Models\School;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionRequest;
use App\Models\User;
use App\Services\ContractService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Super-administration : abonnements des écoles, renouvellements et demandes d'abonnement.
 */
class SubscriptionController extends Controller
{
    public function __construct(private ContractService $contracts) {}

    /**
     * Écoles, offres actives et contrats existants.
     */
    public function index()
    {
        $schools = School::orderBy('name')->get();
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('yearly_price')->get();
        $contracts = Contract::with('school', 'parent')->latest()->get();

        return view('superadmin.subscriptions.index', compact('schools', 'plans', 'contracts'));
    }

    /**
     * Établit le premier contrat d'une école. Refusé si l'école a déjà un contrat en cours : il faut
     * alors le renouveler (nouvelle période) ou établir un avenant (changement en cours de période).
     * Seule la date de début se choisit, la fin est calculée (abonnement annuel).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'plan_id' => 'required|exists:subscription_plans,id',
            'start_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
        ]);

        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $school = School::findOrFail($validated['school_id']);

        if ($this->isDemoSchool($school)) {
            return back()->withErrors(['school_id' => '⚠️ Impossible d\'activer un abonnement pour l\'école de démonstration.'])->withInput();
        }

        if ($current = $this->contracts->activeContractFor($school)) {
            return back()->withErrors(['school_id' => "Cette école a déjà un contrat en cours ({$current->contract_number}, jusqu'au {$current->end_date->format('d/m/Y')}). Renouvelez-le ou établissez un avenant."])->withInput();
        }

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $contract = $this->contracts->issue(
            $school,
            $this->contracts->offerFromPlan($plan),
            $start,
            $this->contracts->endDateFor($start),
            (float) $validated['amount'],
        );

        ActivityLog::logAction('created_contract', "A activé le contrat {$contract->contract_number} pour l'école {$school->name}");

        return redirect()->route('superadmin.subscriptions.index')
            ->with('success', "✅ Contrat {$contract->contract_number} créé pour {$school->name} ! L'école est active et le PDF est prêt.");
    }

    /**
     * Télécharge le PDF d'un contrat ou d'un avenant (régénéré s'il manque).
     */
    public function download(Contract $contract)
    {
        foreach (['local', 'public'] as $disk) {
            if ($contract->pdf_path && Storage::disk($disk)->exists($contract->pdf_path)) {
                return Storage::disk($disk)->response($contract->pdf_path, $contract->contract_number.'.pdf');
            }
        }

        $this->contracts->generatePdf($contract);

        return Storage::disk('local')->response($contract->pdf_path, $contract->contract_number.'.pdf');
    }

    /**
     * Formulaire de renouvellement : la nouvelle période démarre le lendemain de la fin du contrat
     * et dure un an ; ces dates ne se modifient pas.
     */
    public function renew($id)
    {
        $contract = Contract::findOrFail($id);
        abort_unless($contract->status === 'active', 404);

        $newStartDate = $contract->end_date->copy()->addDay();
        $newEndDate = $this->contracts->endDateFor($newStartDate);

        return view('superadmin.subscriptions.renew', compact('contract', 'newStartDate', 'newEndDate'));
    }

    /**
     * Enregistre le renouvellement : nouveau contrat à l'identique sur la période suivante.
     */
    public function storeRenewal(Request $request, $id)
    {
        $oldContract = Contract::findOrFail($id);
        abort_unless($oldContract->status === 'active', 404);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $school = School::findOrFail($oldContract->school_id);
        if ($this->isDemoSchool($school)) {
            return back()->withErrors(['error' => '⚠️ Impossible de renouveler un abonnement pour l\'école de démonstration.']);
        }

        $start = $oldContract->end_date->copy()->addDay();
        $oldContract->update(['status' => 'renewed']);
        $newContract = $this->contracts->issue(
            $school,
            $this->contracts->offerFromContract($oldContract),
            $start,
            $this->contracts->endDateFor($start),
            (float) $validated['amount'],
            'renouvellement',
            $oldContract,
        );

        ActivityLog::logAction('renewed_contract', "A renouvelé le contrat de l'école '{$school->name}' (nouveau contrat {$newContract->contract_number})");

        return redirect()->route('superadmin.subscriptions.index')
            ->with('success', "✅ Contrat renouvelé ! Nouveau contrat {$newContract->contract_number} généré pour {$school->name}.");
    }

    /**
     * Formulaire d'avenant : changement d'offre ou de montant en cours de contrat.
     */
    public function amend(Contract $contract)
    {
        abort_unless($contract->status === 'active' && $contract->end_date->isFuture(), 404);

        $plans = SubscriptionPlan::where('is_active', true)->orderBy('yearly_price')->get();

        return view('superadmin.subscriptions.amend', compact('contract', 'plans'));
    }

    /**
     * Enregistre l'avenant : le contrat d'origine est clos la veille de la date d'effet et l'avenant
     * le remplace jusqu'à la fin de la période initiale (qui ne change pas).
     */
    public function storeAmendment(Request $request, Contract $contract)
    {
        abort_unless($contract->status === 'active' && $contract->end_date->isFuture(), 404);

        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'effective_date' => ['required', 'date', 'after:'.$contract->start_date->format('Y-m-d'), 'before_or_equal:'.$contract->end_date->format('Y-m-d')],
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:500',
        ]);

        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $effective = Carbon::parse($validated['effective_date'])->startOfDay();

        $originalEnd = $contract->end_date->copy();
        $contract->update(['status' => 'amended', 'end_date' => $effective->copy()->subDay()]);
        $amendment = $this->contracts->issue(
            $contract->school,
            $this->contracts->offerFromPlan($plan),
            $effective,
            $originalEnd,
            (float) $validated['amount'],
            'avenant',
            $contract,
        );

        ActivityLog::logAction('amended_contract', "A établi l'avenant {$amendment->contract_number} au contrat {$contract->contract_number} ({$contract->school->name}) : {$validated['reason']}");

        return redirect()->route('superadmin.subscriptions.index')
            ->with('success', "✅ Avenant {$amendment->contract_number} établi pour {$contract->school->name}.");
    }

    /**
     * L'école de démonstration ne peut jamais recevoir d'abonnement.
     */
    private function isDemoSchool(School $school): bool
    {
        return str_contains(strtolower($school->name), 'démo') || str_contains(strtolower($school->email ?? ''), 'demo');
    }

    /**
     * Liste des demandes en attente
     */
    public function pendingRequests()
    {
        $requests = SubscriptionRequest::with(['school.users', 'plan'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        return view('superadmin.subscriptions.pending', compact('requests'));
    }

    /**
     * Valide une demande d'abonnement d'une école (sauf l'école de démo) et crée le contrat.
     */
    public function approveRequest(SubscriptionRequest $subRequest, Request $request)
    {
        $school = $subRequest->school;
        $plan = $subRequest->plan;

        // 🛡️ SÉCURITÉ : Empêcher l'activation de l'école de démo
        if (str_contains(strtolower($school->name), 'démo') || str_contains(strtolower($school->email ?? ''), 'demo')) {
            return redirect()->back()->with('error', '⚠️ Impossible d\'activer un abonnement pour l\'école de démonstration.');
        }

        if ($current = $this->contracts->activeContractFor($school)) {
            return redirect()->back()->with('error', "Cette école a déjà un contrat en cours ({$current->contract_number}) : renouvelez-le ou établissez un avenant.");
        }

        // La demande précise une durée (mensuelle ou annuelle) : période et tarif en dépendent.
        $isMonthly = $subRequest->duration === 'monthly';
        $start = Carbon::today();
        $contract = $this->contracts->issue(
            $school,
            $this->contracts->offerFromPlan($plan),
            $start,
            $this->contracts->endDateFor($start, $isMonthly ? 'monthly' : 'yearly'),
            (float) ($isMonthly ? ($plan->monthly_price ?? $plan->yearly_price) : $plan->yearly_price),
        );
        $contractNumber = $contract->contract_number;

        // 3. METTRE À JOUR LA DEMANDE EXISTANTE (et non en créer une nouvelle)
        $subRequest->update([
            'status' => 'approved', // ou 'active' selon votre enum, mais 'approved' est logique pour une demande
            'admin_notes' => $request->admin_notes ?? null,
        ]);

        // 🚀 4. ENVOYER LES IDENTIFIANTS AU DIRECTEUR
        $director = User::where('school_id', $school->id)
            ->where('role', 'school_admin')
            ->first();

        if ($director) {
            // Mot de passe généré à l'approbation (unique par école) : avant cette étape, le
            // compte créé par storeRequest() a un mot de passe aléatoire inconnu de tous.
            $temporaryPassword = Str::random(12);
            $director->update([
                'password' => Hash::make($temporaryPassword),
            ]);

            Mail::to($director->email)->send(
                new SchoolAdminWelcomeMail($school->name, $director->email, $temporaryPassword)
            );
        }

        ActivityLog::logAction(
            'approved_subscription',
            "A approuvé la demande et activé le contrat {$contractNumber} pour l'école '{$school->name}'"
        );

        return redirect()->route('superadmin.subscriptions.pending')
            ->with('success', "✅ L'école '{$school->name}' a été activée avec succès et le contrat a été généré.");
    }

    /**
     * Refuser une demande
     */
    public function rejectRequest(Request $request, SubscriptionRequest $subRequest)
    {
        $validated = $request->validate([
            'admin_notes' => 'required|string', // Raison du refus
        ]);

        $subRequest->update([
            'status' => 'rejected',
            'admin_notes' => $validated['admin_notes'],
        ]);

        ActivityLog::logAction(
            'rejected_subscription',
            "A refusé la demande d'abonnement de l'école '{$subRequest->school->name}' (motif : {$validated['admin_notes']})"
        );

        return redirect()->route('superadmin.subscriptions.pending')
            ->with('success', "❌ Demande refusée. L'administrateur de l'école sera notifié.");
    }
}
