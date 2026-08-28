<?php

namespace App\Console\Commands;

use App\Mail\ExtraInstallmentLateMail;
use App\Models\ExtraInstallment;
use App\Models\NotificationLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class NotifyExtrasLate extends Command
{
    protected $signature = 'notify:extras-late';

    protected $description = "Envoie une alerte aux parents dont une échéance d'extra est en retard de paiement.";

    public function handle()
    {
        $this->info("🔍 Vérification des échéances d'extras en retard...");

        $installments = ExtraInstallment::whereIn('status', ['pending', 'partial'])
            ->whereDate('due_date', '<', Carbon::today())
            ->with(['subscription.student', 'subscription.extra', 'subscription.school'])
            ->get();

        if ($installments->isEmpty()) {
            $this->info('✅ Aucune échéance en retard.');

            return;
        }

        $this->warn('⚠️ '.$installments->count().' échéance(s) en retard trouvée(s). Envoi des emails...');

        foreach ($installments as $installment) {
            $student = $installment->subscription->student;

            if (NotificationLog::alreadySentForExtraInstallment($installment->id, 'extra_late', 'email')) {
                $this->line("⏭️  Échéance extra #{$installment->id} déjà notifiée, on passe.");

                continue;
            }

            if (empty($student->guardian_email)) {
                $this->error("❌ Pas d'email configuré pour le parent de : {$student->first_name} {$student->last_name}");

                continue;
            }

            $status = 'sent';
            $errorMessage = null;

            try {
                Mail::to($student->guardian_email)->send(new ExtraInstallmentLateMail($student, $installment));
                $this->line("✅ Email envoyé à : {$student->guardian_email} (Parent de : {$student->first_name} {$student->last_name})");
            } catch (\Exception $e) {
                $status = 'failed';
                $errorMessage = $e->getMessage();
                $this->error("❌ Échec d'envoi pour {$student->first_name} {$student->last_name} : {$errorMessage}");
            }

            NotificationLog::create([
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'extra_installment_id' => $installment->id,
                'type' => 'email',
                'category' => 'extra_late',
                'recipient_email' => $student->guardian_email,
                'message' => "Alerte échéance extra en retard : {$installment->subscription->extra->name}",
                'status' => $status,
                'error_message' => $errorMessage,
            ]);
        }

        $this->info('✨ Traitement terminé !');
    }
}
