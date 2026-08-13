<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StudentInstallment;
use App\Models\NotificationLog;
use App\Services\OrangeSmsService;
use Illuminate\Support\Facades\Log;

class SendLatePaymentNotifications extends Command
{
    protected $signature = 'notifications:late-payments';
    protected $description = 'Envoyer des notifications SMS aux parents pour les paiements en retard';

    private $smsService;

    public function __construct(OrangeSmsService $smsService)
    {
        parent::__construct();
        $this->smsService = $smsService;
    }

    public function handle()
    {
        $this->info('🔍 Détection des échéances en retard...');

        $lateInstallments = StudentInstallment::where('status', '!=', 'paid')
            ->where('due_date', '<', now())
            ->whereHas('enrollment.student.parents')
            ->with(['enrollment.student.parents', 'enrollment.student.school'])
            ->get();

        $this->info("📊 {$lateInstallments->count()} échéances en retard trouvées.");

        $sentCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        foreach ($lateInstallments as $installment) {
            // Vérifier si déjà notifié
            if (NotificationLog::alreadySentForInstallment($installment->id, 'sms')) {
                $this->line("⏭️  Échéance #{$installment->id} déjà notifiée, on passe.");
                $skippedCount++;
                continue;
            }

            $student = $installment->enrollment->student;
            $parent = $student->parents->first();

            if (!$parent || !$parent->phone) {
                $this->warn("⚠️  Pas de téléphone pour le parent de {$student->first_name} {$student->last_name}");
                continue;
            }

            $remainingAmount = $installment->amount - $installment->paid_amount;
            $message = $this->buildMessage($student, $installment, $remainingAmount);

            $this->info("📤 Envoi SMS au parent de {$student->first_name} {$student->last_name}...");

            $result = $this->smsService->sendSms($parent->phone, $message);

            NotificationLog::create([
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'installment_id' => $installment->id,
                'parent_id' => $parent->id,
                'type' => 'sms',
                'category' => 'late_payment',
                'recipient_phone' => $parent->phone,
                'message' => $message,
                'status' => $result['success'] ? 'sent' : 'failed',
                'error_message' => $result['error'] ?? null,
                'provider_response_id' => $result['message_id'] ?? null,
            ]);

            if ($result['success']) {
                $this->info("✅ SMS envoyé avec succès (ID: {$result['message_id']})");
                $sentCount++;
            } else {
                $this->error("❌ Échec: {$result['error']}");
                $failedCount++;
            }

            sleep(1); // Pause pour ne pas surcharger l'API
        }

        $this->newLine();
        $this->info("📊 Résumé :");
        $this->line("   ✅ Envoyés : {$sentCount}");
        $this->line("   ⏭️  Déjà notifiés : {$skippedCount}");
        $this->line("   ❌ Échecs : {$failedCount}");

        return 0;
    }

    private function buildMessage($student, $installment, $remainingAmount): string
    {
        $schoolName = $student->school->name ?? 'L\'école';
        $dueDate = \Carbon\Carbon::parse($installment->due_date)->format('d/m/Y');
        $description = $installment->description;

        return "Bonjour, nous vous rappelons que l'échéance '{$description}' de " .
               number_format($remainingAmount, 0, ',', ' ') . " FCFA pour " .
               "{$student->first_name} {$student->last_name} était prévue le {$dueDate}. " .
               "Merci de régulariser la situation. - {$schoolName}";
    }
}