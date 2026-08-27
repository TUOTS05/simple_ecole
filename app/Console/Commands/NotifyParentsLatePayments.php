<?php

namespace App\Console\Commands;

use App\Mail\ParentLatePaymentMail;
use App\Models\StudentInstallment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class NotifyParentsLatePayments extends Command
{
    protected $signature = 'notify:parents-late';

    protected $description = 'Envoie une alerte aux parents dont les enfants ont des échéances en retard.';

    public function handle()
    {
        $this->info('🔍 Vérification des échéances en retard...');

        // Trouver les échéances en retard (date passée et pas encore payées)
        $lateInstallments = StudentInstallment::where('status', 'pending')
            ->whereDate('due_date', '<', Carbon::today())
            ->with(['enrollment.student', 'enrollment.schoolClass'])
            ->get();

        if ($lateInstallments->isEmpty()) {
            $this->info('✅ Aucune échéance en retard.');

            return;
        }

        $this->warn('⚠️ '.$lateInstallments->count().' échéance(s) en retard trouvée(s). Envoi des emails...');

        // Grouper par élève pour éviter d'envoyer plusieurs emails au même parent
        $groupedByStudent = $lateInstallments->groupBy('enrollment.student.id');

        foreach ($groupedByStudent as $studentId => $installments) {
            $student = $installments->first()->enrollment->student;

            // Vérifier que l'élève a un email de gardien
            if (! empty($student->guardian_email)) {
                // Envoyer un email pour la première échéance en retard
                $firstLateInstallment = $installments->first();

                Mail::to($student->guardian_email)->send(new ParentLatePaymentMail($student, $firstLateInstallment));

                $this->line("✅ Email envoyé à : {$student->guardian_email} (Parent de : {$student->first_name} {$student->last_name})");
            } else {
                $this->error("❌ Pas d'email configuré pour le parent de : {$student->first_name} {$student->last_name}");
            }
        }

        $this->info('✨ Traitement terminé !');
    }
}
