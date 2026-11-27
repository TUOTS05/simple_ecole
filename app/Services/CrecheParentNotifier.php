<?php

namespace App\Services;

use App\Models\SmsLog;
use App\Models\Student;
use App\Services\Sms\OrangeSmsGateway;

/**
 * Notifications de la crèche aux parents (arrivée, départ, incident, carnet du jour) par SMS,
 * via la passerelle et le journal SMS existants. Envoyées seulement si l'école a activé les
 * SMS (Configuration SMS) et l'événement concerné (Crèche > Paramètres).
 *
 * Règle 10 : les messages n'exposent que le prénom de l'enfant. L'envoi se fait après la
 * réponse HTTP, pour ne pas ralentir le pointage à l'entrée de la crèche.
 */
class CrecheParentNotifier
{
    public const EVENTS = [
        'arrival' => 'Arrivée de l\'enfant',
        'departure' => 'Départ de l\'enfant',
        'incident' => 'Incident validé',
        'summary' => 'Carnet du jour publié',
    ];

    public function arrival(Student $student, string $time): void
    {
        $this->notify($student, 'arrival', "{$student->first_name} est bien arrivé(e) à la crèche à ".$this->time($time).'.');
    }

    public function departure(Student $student, string $time, string $pickupName): void
    {
        $this->notify($student, 'departure', "{$student->first_name} a quitté la crèche à ".$this->time($time)." avec {$pickupName}.");
    }

    public function incident(Student $student): void
    {
        $this->notify($student, 'incident', "Une observation concernant votre enfant {$student->first_name} nécessite votre attention. Consultez votre espace parent ou contactez la crèche.");
    }

    public function summary(Student $student): void
    {
        $this->notify($student, 'summary', "Le carnet du jour de {$student->first_name} est disponible dans votre espace parent.");
    }

    public function isEnabled(Student $student, string $event): bool
    {
        $school = $student->school;

        return $school && $school->sms_enabled && (bool) $school->setting("creche.notify_{$event}");
    }

    private function notify(Student $student, string $event, string $text): void
    {
        if (! $this->isEnabled($student, $event)) {
            return;
        }

        $school = $student->school;
        $message = $text.' '.$school->name;
        $parents = $student->parents()->whereNotNull('phone')->where('phone', '!=', '')->get();

        foreach ($parents as $parent) {
            $log = SmsLog::create([
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'recipient_phone' => $parent->phone,
                'recipient_name' => $parent->full_name,
                'message' => $message,
                'gateway' => 'orange_sms',
                'status' => 'pending',
                'trigger_type' => "creche_{$event}",
            ]);

            dispatch(function () use ($log) {
                $result = (new OrangeSmsGateway($log->school_id))->send($log->recipient_phone, $log->message);
                $log->update([
                    'status' => $result['success'] ? 'sent' : 'failed',
                    'external_id' => $result['external_id'] ?? null,
                    'error_message' => $result['error'] ?? null,
                    'sent_at' => $result['success'] ? now() : null,
                ]);
            })->afterResponse();
        }
    }

    private function time(string $time): string
    {
        return str_replace(':', 'h', substr($time, 0, 5));
    }
}
