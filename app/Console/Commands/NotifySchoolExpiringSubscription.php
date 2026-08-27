<?php

namespace App\Console\Commands;

use App\Mail\SchoolSubscriptionExpiringMail;
use App\Models\School;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class NotifySchoolExpiringSubscription extends Command
{
    /**
     * Le nom et la signature de la commande console.
     */
    protected $signature = 'notify:schools-expiring';

    /**
     * La description de la commande.
     */
    protected $description = 'Envoie une alerte aux écoles dont l\'abonnement ou l\'essai gratuit expire dans 30 jours.';

    /**
     * Exécution de la commande.
     */
    public function handle()
    {
        $this->info('🔍 Vérification des abonnements et essais gratuits expirant dans 30 jours...');

        // Date cible : aujourd'hui + 30 jours
        $targetDate = Carbon::today()->addDays(30)->format('Y-m-d');

        // Abonnements payants expirant dans 30 jours
        $subscriptionsExpiring = School::whereDate('subscription_end_date', $targetDate)->get();

        // Essais gratuits expirant dans 30 jours : uniquement les écoles sans abonnement payant en
        // cours (sinon on préviendrait pour un essai qui n'a plus cours depuis l'activation payante).
        $trialsExpiring = School::whereDate('trial_ends_at', $targetDate)
            ->whereNull('subscription_end_date')
            ->get();

        if ($subscriptionsExpiring->isEmpty() && $trialsExpiring->isEmpty()) {
            $this->info('✅ Aucune école n\'expire dans exactement 30 jours.');

            return;
        }

        $notified = 0;

        foreach ($subscriptionsExpiring as $school) {
            if (! empty($school->email)) {
                Mail::to($school->email)->send(new SchoolSubscriptionExpiringMail($school, $school->subscription_end_date, false));
                $this->line("✅ Email envoyé (abonnement) à : {$school->email} (École: {$school->name})");
                $notified++;
            } else {
                $this->error("❌ Pas d'email configuré pour l'école : {$school->name}");
            }
        }

        foreach ($trialsExpiring as $school) {
            if (! empty($school->email)) {
                Mail::to($school->email)->send(new SchoolSubscriptionExpiringMail($school, $school->trial_ends_at, true));
                $this->line("✅ Email envoyé (essai gratuit) à : {$school->email} (École: {$school->name})");
                $notified++;
            } else {
                $this->error("❌ Pas d'email configuré pour l'école : {$school->name}");
            }
        }

        $this->info("✨ {$notified} notification(s) envoyée(s).");
    }
}
