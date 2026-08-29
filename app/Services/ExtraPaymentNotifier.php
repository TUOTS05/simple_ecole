<?php

namespace App\Services;

use App\Mail\ExtraPaymentConfirmedMail;
use App\Models\ExtraPayment;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Mail;

/**
 * Envoie l'email de confirmation de paiement d'un extra et journalise l'envoi
 * (NotificationLog). Extrait de ExtraController pour être réutilisable depuis
 * le flux de paiement en ligne (ExtraOnlinePaymentService), déclenché hors
 * contexte HTTP authentifié (webhook CinetPay).
 */
class ExtraPaymentNotifier
{
    public function __construct(private ExtraWhatsAppNotifier $whatsapp) {}

    public function sendConfirmation(ExtraPayment $payment): void
    {
        $payment->loadMissing('subscription.student', 'subscription.extra');
        $student = $payment->subscription->student;

        // Canal indépendant de l'email : un parent sans adresse peut avoir
        // un numéro WhatsApp, d'où l'envoi avant le retour anticipé ci-dessous.
        $this->whatsapp->sendPaymentConfirmed($payment);

        if (empty($student->guardian_email)) {
            return;
        }

        $status = 'sent';
        $errorMessage = null;

        try {
            Mail::to($student->guardian_email)->send(new ExtraPaymentConfirmedMail($student, $payment));
        } catch (\Exception $e) {
            $status = 'failed';
            $errorMessage = $e->getMessage();
        }

        NotificationLog::create([
            'school_id' => $payment->school_id,
            'student_id' => $student->id,
            'type' => 'email',
            'category' => 'extra_payment_confirmed',
            'recipient_email' => $student->guardian_email,
            'message' => 'Confirmation de paiement extra : '.number_format($payment->amount, 0, ',', ' ')." FCFA pour « {$payment->subscription->extra->name} »",
            'status' => $status,
            'error_message' => $errorMessage,
        ]);
    }
}
