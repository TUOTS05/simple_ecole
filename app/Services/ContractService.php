<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Émission des contrats d'abonnement. Un contrat signé n'est jamais modifié : une nouvelle période
 * passe par un renouvellement, un changement en cours de période par un avenant rattaché au contrat
 * d'origine. Chaque émission met l'école, la table `subscriptions` et le PDF en cohérence.
 */
class ContractService
{
    /**
     * Contrat en cours de l'école (actif et non échu), s'il y en a un.
     */
    public function activeContractFor(School $school): ?Contract
    {
        return Contract::where('school_id', $school->id)
            ->where('status', 'active')
            ->whereDate('end_date', '>=', Carbon::today())
            ->latest('start_date')
            ->first();
    }

    /**
     * Fin d'une période d'abonnement : un an (ou un mois) moins un jour après le début.
     */
    public function endDateFor(Carbon $start, string $duration = 'yearly'): Carbon
    {
        return ($duration === 'monthly' ? $start->copy()->addMonth() : $start->copy()->addYear())->subDay();
    }

    /**
     * Établit un contrat (ou un renouvellement, ou un avenant) et active l'école en conséquence.
     *
     * @param  array{plan: SubscriptionPlan|null, plan_name: string, max_students: int, max_users: int}  $offer
     */
    public function issue(
        School $school,
        array $offer,
        Carbon $start,
        Carbon $end,
        float $amount,
        string $type = 'contrat',
        ?Contract $parent = null,
    ): Contract {
        return DB::transaction(function () use ($school, $offer, $start, $end, $amount, $type, $parent) {
            $contract = Contract::create([
                'school_id' => $school->id,
                'contract_number' => ($type === 'avenant' ? 'AVN-' : 'CTR-').$start->format('Y').'-'.strtoupper(Str::random(6)),
                'type' => $type,
                'parent_contract_id' => $parent?->id,
                'plan_name' => $offer['plan_name'],
                'start_date' => $start,
                'end_date' => $end,
                'amount' => $amount,
                'max_students' => $offer['max_students'],
                'max_users' => $offer['max_users'],
                'status' => 'active',
                'signed_at' => now(),
            ]);

            $school->update([
                'status' => 'active',
                'is_active' => true,
                'subscription_plan' => $offer['plan_name'],
                'subscription_start_date' => $start,
                'subscription_end_date' => $end,
                'max_students' => $offer['max_students'] ?: 999999,
                'max_users' => $offer['max_users'] ?: 999999,
                'trial_ends_at' => null, // un abonnement payant met fin à l'essai gratuit
            ]);

            // La table `subscriptions` suit le contrat en vigueur.
            Subscription::where('school_id', $school->id)->where('status', 'active')->update(['status' => 'expired']);
            if ($offer['plan']) {
                Subscription::create([
                    'school_id' => $school->id,
                    'plan_id' => $offer['plan']->id,
                    'plan_name' => $offer['plan_name'],
                    'start_date' => $start,
                    'end_date' => $end,
                    'amount' => $amount,
                    'status' => 'active',
                ]);
            }

            $this->ensureActiveSchoolYear($school);
            $this->generatePdf($contract);

            return $contract;
        });
    }

    /**
     * Offre décrite par un plan d'abonnement.
     */
    public function offerFromPlan(SubscriptionPlan $plan): array
    {
        return [
            'plan' => $plan,
            'plan_name' => $plan->name,
            'max_students' => (int) ($plan->max_students ?? 0),
            'max_users' => (int) ($plan->max_users ?? 0),
        ];
    }

    /**
     * Offre reprise d'un contrat existant (renouvellement à l'identique).
     */
    public function offerFromContract(Contract $contract): array
    {
        return [
            'plan' => SubscriptionPlan::where('name', $contract->plan_name)->first(),
            'plan_name' => $contract->plan_name,
            'max_students' => (int) $contract->max_students,
            'max_users' => (int) $contract->max_users,
        ];
    }

    /**
     * (Re)génère le PDF du contrat, conservé sur le disque privé.
     */
    public function generatePdf(Contract $contract): void
    {
        $contract->loadMissing('school', 'parent');
        $pdf = Pdf::loadView('pdf.contract', ['contract' => $contract, 'school' => $contract->school]);

        $path = 'contracts/'.$contract->contract_number.'.pdf';
        Storage::disk('local')->put($path, $pdf->output());
        $contract->update(['pdf_path' => $path]);
    }

    /**
     * Sans année scolaire active, les modules métier (cantine, bulletins, fin d'année…) ne fonctionnent pas.
     */
    private function ensureActiveSchoolYear(School $school): void
    {
        if (SchoolYear::where('school_id', $school->id)->where('is_active', true)->exists()) {
            return;
        }

        $yearStart = now()->month >= 8 ? now()->year : now()->year - 1;
        SchoolYear::firstOrCreate(
            ['school_id' => $school->id, 'name' => $yearStart.'-'.($yearStart + 1)],
            [
                'start_date' => Carbon::createFromDate($yearStart, 9, 1),
                'end_date' => Carbon::createFromDate($yearStart + 1, 6, 30),
                'is_active' => true,
            ]
        );
    }
}
