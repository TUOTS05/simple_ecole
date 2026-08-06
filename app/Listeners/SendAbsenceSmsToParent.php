<?php

namespace App\Listeners;

use App\Events\StudentMarkedAbsent;
use App\Models\SmsLog;
use App\Services\Sms\OrangeSmsGateway;
use Illuminate\Support\Facades\Log;
use Illuminate\Events\Listeners\Listening;

//[Listening(events: [StudentMarkedAbsent::class])]
class SendAbsenceSmsToParent
{
    public function handle(StudentMarkedAbsent $event): void
    {
        $attendance = $event->attendance;

        // On ne traite que les absences
        if ($attendance->status !== 'absent') {
            return;
        }

        $student = $attendance->student;
        if (!$student) {
            return;
        }

        // Récupérer l'école pour avoir les paramètres SMS
        $school = $student->school;

        // Récupérer les parents de l'élève
        $parents = $student->parents;
        if ($parents->isEmpty()) {
            Log::warning("Aucun parent trouvé pour l'élève {$student->id}");
            return;
        }

        // ✅ MODIFICATION ICI : On lit le template directement depuis la table schools
        $template = $school->sms_absence_template 
            ?? 'Cher(e) parent, nous vous informons que votre enfant {student_name} ({class_name}) a été absent(e) le {date}. Merci de contacter l\'établissement. {school_name}';

        $schoolClass = $attendance->schoolClass;

        foreach ($parents as $parent) {
            if (empty($parent->phone)) {
                continue;
            }

            // Remplacer les variables dans le template
            $message = str_replace(
                ['{parent_name}', '{student_name}', '{class_name}', '{date}', '{school_name}', '{school_phone}'],
                [
                    trim(($parent->first_name ?? '') . ' ' . ($parent->last_name ?? '')) ?: 'Parent',
                    trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')) ?: 'Élève',
                    $schoolClass->name ?? 'N/A',
                    \Carbon\Carbon::parse($attendance->date)->isoFormat('DD/MM/YYYY'),
                    $school->name ?? 'L\'école',
                    $school->phone ?? '',
                ],
                $template
            );

            // Créer le log AVANT l'envoi
            $smsLog = SmsLog::create([
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'recipient_phone' => $parent->phone,
                'recipient_name' => trim(($parent->first_name ?? '') . ' ' . ($parent->last_name ?? '')),
                'message' => $message,
                'gateway' => 'orange_sms',
                'status' => 'pending',
                'trigger_type' => 'absence',
            ]);

            // Envoyer le SMS
            $gateway = new OrangeSmsGateway($student->school_id);
            $result = $gateway->send($parent->phone, $message);

            // Mettre à jour le log avec le résultat
            $smsLog->update([
                'status' => $result['success'] ? 'sent' : 'failed',
                'external_id' => $result['external_id'],
                'error_message' => $result['error'],
                'sent_at' => $result['success'] ? now() : null,
            ]);

            if ($result['success']) {
                Log::info("✅ SMS envoyé avec succès à {$parent->phone} pour l'élève {$student->id}");
            } else {
                Log::error("❌ Échec envoi SMS à {$parent->phone} : {$result['error']}");
            }
        }
    }
}