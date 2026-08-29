<?php

namespace App\Console\Commands;

use App\Mail\ExtraInstallmentUpcomingMail;
use App\Models\ExtraInstallment;
use App\Models\NotificationLog;
use App\Services\ExtraWhatsAppNotifier;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class NotifyExtrasUpcoming extends Command
{
    protected $signature = 'notify:extras-upcoming {--days=3 : Nombre de jours avant échéance pour déclencher le rappel}';

    protected $description = "Envoie un rappel aux parents dont une échéance d'extra arrive bientôt à échéance.";

    public function handle(ExtraWhatsAppNotifier $whatsapp)
    {
        $days = (int) $this->option('days');
        $targetDate = Carbon::today()->addDays($days);

        $this->info("🔍 Recherche des échéances d'extras dues le {$targetDate->format('d/m/Y')}...");

        $installments = ExtraInstallment::whereIn('status', ['pending', 'partial'])
            ->whereDate('due_date', $targetDate)
            ->with(['subscription.student', 'subscription.extra', 'subscription.school'])
            ->get();

        if ($installments->isEmpty()) {
            $this->info('✅ Aucune échéance à venir concernée.');

            return;
        }

        $this->warn('⚠️ '.$installments->count().' échéance(s) trouvée(s). Envoi des emails...');

        foreach ($installments as $installment) {
            $student = $installment->subscription->student;

            // WhatsApp d'abord, avant tout "continue" propre à l'email : les deux
            // canaux sont indépendants (un parent sans email peut avoir un numéro).
            $sent = $whatsapp->sendUpcoming($installment);
            if ($sent !== null) {
                $this->line($sent
                    ? "💬 WhatsApp envoyé pour : {$student->first_name} {$student->last_name}"
                    : "❌ Échec WhatsApp pour : {$student->first_name} {$student->last_name}");
            }

            if (NotificationLog::alreadySentForExtraInstallment($installment->id, 'extra_upcoming', 'email')) {
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
                Mail::to($student->guardian_email)->send(new ExtraInstallmentUpcomingMail($student, $installment));
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
                'category' => 'extra_upcoming',
                'recipient_email' => $student->guardian_email,
                'message' => "Rappel échéance extra à venir : {$installment->subscription->extra->name}",
                'status' => $status,
                'error_message' => $errorMessage,
            ]);
        }

        $this->info('✨ Traitement terminé !');
    }
}
