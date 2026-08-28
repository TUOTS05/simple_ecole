<?php

namespace App\Console\Commands;

use App\Models\ExtraInstallment;
use App\Models\ExtraSubscription;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateRecurringExtraInstallments extends Command
{
    protected $signature = 'extras:generate-monthly-installments';

    protected $description = "Génère l'échéance du mois pour les abonnements extras à facturation mensuelle continue (tarif « is_open_ended »).";

    public function handle()
    {
        $currentPeriod = now()->format('Y-m');

        $this->info("🔍 Génération des échéances de {$currentPeriod} pour les abonnements à facturation continue...");

        $subscriptions = ExtraSubscription::where('status', 'active')
            ->whereHas('extraTarif', fn ($q) => $q->where('is_open_ended', true))
            ->with('extraTarif', 'extra', 'student')
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($subscriptions as $subscription) {
            // Idempotent : ne recrée jamais une échéance déjà générée pour ce mois
            // (que ce soit par ce même run planifié rejoué, ou par la création
            // initiale de l'abonnement si elle a eu lieu ce mois-ci).
            if ($subscription->installments()->where('period', $currentPeriod)->exists()) {
                $skipped++;

                continue;
            }

            $tarif = $subscription->extraTarif;
            $dueDate = Carbon::parse($currentPeriod.'-01')->day(min($tarif->due_day, Carbon::parse($currentPeriod.'-01')->daysInMonth));

            ExtraInstallment::create([
                'extra_subscription_id' => $subscription->id,
                'period' => $currentPeriod,
                'amount' => $tarif->amount,
                'paid_amount' => 0,
                'due_date' => $dueDate,
                'status' => 'pending',
            ]);

            $subscription->total_amount += $tarif->amount;
            $subscription->remaining_amount += $tarif->amount;
            $subscription->save();

            $this->line("✅ {$subscription->extra->name} — {$subscription->student->first_name} {$subscription->student->last_name}");
            $created++;
        }

        $this->info("✨ Terminé : {$created} échéance(s) créée(s), {$skipped} déjà à jour.");
    }
}
