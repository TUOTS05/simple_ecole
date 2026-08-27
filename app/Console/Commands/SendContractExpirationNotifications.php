<?php

namespace App\Console\Commands;

use App\Mail\ContractExpiringSoon;
use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendContractExpirationNotifications extends Command
{
    protected $signature = 'contracts:notify-expiration';

    protected $description = 'Envoie un email de rappel 30 jours avant l\'expiration des contrats actifs';

    public function handle()
    {
        // On cherche les contrats qui expirent dans exactement 30 jours
        $targetDate = Carbon::now()->addDays(30)->format('Y-m-d');

        $expiringContracts = Contract::where('status', 'active')
            ->whereDate('end_date', $targetDate)
            ->with('school')
            ->get();

        $count = 0;
        foreach ($expiringContracts as $contract) {
            if ($contract->school && $contract->school->email) {
                // Envoyer l'email à l'école
                Mail::to($contract->school->email)->send(new ContractExpiringSoon($contract));

                // Optionnel : Envoyer une copie au Super Admin pour suivi
                // Mail::to('superadmin@votre-domaine.com')->send(new ContractExpiringSoon($contract));

                $count++;
            }
        }

        $this->info("✅ {$count} notification(s) d'expiration envoyée(s) avec succès.");

        return Command::SUCCESS;
    }
}
