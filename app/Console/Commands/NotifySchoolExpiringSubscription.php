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
    protected $description = 'Envoie une alerte aux écoles dont l\'abonnement expire dans 30 jours.';

    /**
     * Exécution de la commande.
     */
    public function handle()
    {
        $this->info('🔍 Vérification des abonnements expirant dans 30 jours...');

        // Date cible : aujourd'hui + 30 jours
        $targetDate = Carbon::today()->addDays(30)->format('Y-m-d');
        
        // Récupération des écoles (avec le bon nom de colonne)
        $schools = School::whereDate('subscription_end_date', $targetDate)->get();

        if ($schools->isEmpty()) {
            $this->info('✅ Aucune école n\'expire dans exactement 30 jours.');
            return;
        }

        $this->warn("⚠️ " . $schools->count() . " école(s) trouvée(s). Envoi des emails...");

        foreach ($schools as $school) {
            // Vérifier que l'école a bien une adresse email
            if (!empty($school->email)) {
                // ✅ Envoi de l'email
                Mail::to($school->email)->send(new SchoolSubscriptionExpiringMail($school));
                $this->line("✅ Email envoyé à : {$school->email} (École: {$school->name})");
            } else {
                $this->error("❌ Pas d'email configuré pour l'école : {$school->name}");
            }
        }

        $this->info('✨ Traitement terminé !');
    }
}