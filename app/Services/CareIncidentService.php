<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\CareIncident;
use App\Models\Student;
use App\Models\User;
use DomainException;

/**
 * Incidents de la crèche : déclaration, correction tant qu'il n'est pas validé, validation,
 * annulation et suivis. Un incident validé ou annulé est figé ; il n'est jamais supprimé.
 * Chaque étape est inscrite au journal d'audit (sujet : l'incident, avec l'enfant en meta).
 */
class CareIncidentService
{
    public function __construct(private CrecheParentNotifier $notifier) {}

    public function declare(Student $student, User $user, array $data): CareIncident
    {
        $incident = new CareIncident($data);
        $incident->school_id = $student->school_id;
        $incident->student_id = $student->id;
        $incident->school_class_id = $student->classes()->where('cycle', 'creche')->value('school_classes.id');
        $incident->reported_by = $user->id;
        $incident->status = 'declared';
        $incident->save();

        $this->log($incident, 'creche.incident.declared',
            "Incident déclaré ({$incident->type_label}) pour {$student->first_name} {$student->last_name}",
            ['after' => $incident->auditSnapshot()]);

        return $incident;
    }

    public function update(CareIncident $incident, User $user, array $data): CareIncident
    {
        $this->ensureEditable($incident);
        $before = $incident->auditSnapshot();

        $incident->fill($data);
        $incident->save();

        $this->log($incident, 'creche.incident.updated',
            "Incident modifié ({$incident->type_label}) pour {$incident->student->first_name} {$incident->student->last_name}",
            ['before' => $before, 'after' => $incident->auditSnapshot()]);

        return $incident;
    }

    public function validate(CareIncident $incident, User $user, ?string $comment): CareIncident
    {
        $this->ensureEditable($incident);

        $incident->status = 'validated';
        $incident->validated_by = $user->id;
        $incident->validated_at = now();
        $incident->validation_comment = $comment;
        $incident->save();

        $this->log($incident, 'creche.incident.validated',
            "Incident validé ({$incident->type_label}) pour {$incident->student->first_name} {$incident->student->last_name}",
            ['comment' => $comment, 'validated' => $incident->auditSnapshot()]);

        // Les parents sont prévenus une fois l'incident validé (jamais sur une simple déclaration).
        $this->notifier->incident($incident->student);

        return $incident;
    }

    /** Déclaration erronée : annulée avec son motif, mais conservée. */
    public function cancel(CareIncident $incident, User $user, string $reason): CareIncident
    {
        $this->ensureEditable($incident);

        $incident->status = 'cancelled';
        $incident->validated_by = $user->id;
        $incident->validated_at = now();
        $incident->validation_comment = $reason;
        $incident->save();

        $this->log($incident, 'creche.incident.cancelled',
            "Incident annulé pour {$incident->student->first_name} {$incident->student->last_name} : {$reason}",
            ['reason' => $reason]);

        return $incident;
    }

    /** Suivi ajouté à un incident (soins, appel des parents...) : ajout seul, jamais modifié. */
    public function addFollowUp(CareIncident $incident, User $user, string $text): CareIncident
    {
        if ($incident->status === 'cancelled') {
            throw new DomainException('Un incident annulé ne peut pas recevoir de suivi.');
        }

        $incident->follow_ups = [...($incident->follow_ups ?? []), [
            'at' => now()->format('Y-m-d H:i'),
            'by' => $user->id,
            'by_name' => $user->full_name,
            'text' => $text,
        ]];
        $incident->save();

        $this->log($incident, 'creche.incident.follow_up',
            "Suivi ajouté à l'incident de {$incident->student->first_name} {$incident->student->last_name}",
            ['text' => $text]);

        return $incident;
    }

    private function ensureEditable(CareIncident $incident): void
    {
        if (! $incident->isEditable()) {
            throw new DomainException('Cet incident est '.mb_strtolower($incident->status_label).' : il ne peut plus être modifié. Ajoutez un suivi si nécessaire.');
        }
    }

    private function log(CareIncident $incident, string $action, string $description, array $meta): void
    {
        ActivityLog::audit($action, $description, $incident, ['student_id' => $incident->student_id] + $meta);
    }
}
