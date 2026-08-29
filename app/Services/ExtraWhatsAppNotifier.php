<?php

namespace App\Services;

use App\Models\ExtraInstallment;
use App\Models\ExtraPayment;
use App\Models\NotificationLog;
use App\Models\Student;

/**
 * Envoie les notifications WhatsApp du module Extras et les journalise
 * (NotificationLog, type "whatsapp"), en complément des emails déjà envoyés.
 *
 * Le WhatsApp ne remplace pas l'email : un parent sans numéro reçoit
 * toujours l'email, et inversement. Chaque canal est journalisé séparément,
 * donc dédoublonné séparément.
 */
class ExtraWhatsAppNotifier
{
    public function __construct(private WhatsAppService $whatsapp) {}

    public function sendUpcoming(ExtraInstallment $installment): ?bool
    {
        $installment->loadMissing('subscription.student', 'subscription.extra');
        $student = $installment->subscription->student;

        return $this->send(
            $student,
            'extra_upcoming',
            [
                $student->first_name,
                $installment->subscription->extra->name,
                $this->money($installment->remaining_amount ?? $installment->amount),
                $installment->due_date?->format('d/m/Y') ?? '',
            ],
            "Rappel : {$installment->subscription->extra->name} — "
                .$this->money($installment->remaining_amount ?? $installment->amount)
                .' FCFA à régler avant le '.($installment->due_date?->format('d/m/Y') ?? ''),
            $installment
        );
    }

    public function sendLate(ExtraInstallment $installment): ?bool
    {
        $installment->loadMissing('subscription.student', 'subscription.extra');
        $student = $installment->subscription->student;

        return $this->send(
            $student,
            'extra_late',
            [
                $student->first_name,
                $installment->subscription->extra->name,
                $this->money($installment->remaining_amount ?? $installment->amount),
                $installment->due_date?->format('d/m/Y') ?? '',
            ],
            "Retard de paiement : {$installment->subscription->extra->name} — "
                .$this->money($installment->remaining_amount ?? $installment->amount)
                .' FCFA, échéance du '.($installment->due_date?->format('d/m/Y') ?? ''),
            $installment
        );
    }

    public function sendPaymentConfirmed(ExtraPayment $payment): ?bool
    {
        $payment->loadMissing('subscription.student', 'subscription.extra');
        $student = $payment->subscription->student;

        return $this->send(
            $student,
            'extra_payment_confirmed',
            [
                $student->first_name,
                $this->money($payment->amount),
                $payment->subscription->extra->name,
            ],
            'Paiement reçu : '.$this->money($payment->amount)
                ." FCFA pour « {$payment->subscription->extra->name} ». Merci !",
            null
        );
    }

    /**
     * @return bool|null true/false selon l'envoi, null si rien n'a été tenté
     *                   (pas de numéro, ou déjà notifié pour cette échéance)
     */
    private function send(
        Student $student,
        string $category,
        array $params,
        string $preview,
        ?ExtraInstallment $installment
    ): ?bool {
        $phone = $this->phoneFor($student);

        if ($phone === null) {
            return null;
        }

        if ($installment && NotificationLog::alreadySentForExtraInstallment($installment->id, $category, 'whatsapp')) {
            return null;
        }

        $result = $this->whatsapp->sendTemplate(
            $phone,
            config("services.whatsapp.templates.{$category}"),
            $params,
            $preview
        );

        NotificationLog::create([
            'school_id' => $student->school_id,
            'student_id' => $student->id,
            'extra_installment_id' => $installment?->id,
            'type' => 'whatsapp',
            'category' => $category,
            'recipient_phone' => $result['recipient'] ?? $phone,
            'message' => $preview,
            'status' => $result['status'],
            'error_message' => $result['error'] ?? null,
            'provider_response_id' => $result['message_id'] ?? null,
        ]);

        return (bool) $result['success'];
    }

    /**
     * Premier numéro renseigné parmi le tuteur, la mère, le père.
     */
    private function phoneFor(Student $student): ?string
    {
        foreach ([$student->guardian_phone, $student->mother_phone, $student->father_phone] as $phone) {
            if (! empty($phone)) {
                return $phone;
            }
        }

        return null;
    }

    private function money($amount): string
    {
        return number_format((float) $amount, 0, ',', ' ');
    }
}
