<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AuthorizedPickup;
use App\Models\CrecheAttendance;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonInterface;
use DomainException;

/**
 * Arrivées, absences et départs des enfants de la crèche, avec les règles du cahier des charges :
 *  - une seule présence active par enfant à la fois (pas de double arrivée) ;
 *  - pas de départ sans arrivée, ni de second départ sans nouvelle arrivée ;
 *  - une personne non autorisée (inconnue, interdite, hors période, retirée) ne peut pas
 *    récupérer l'enfant : le départ est bloqué et la tentative journalisée.
 * Toutes les opérations se font sur la journée en cours ; les corrections passent par la grille
 * réservée à la responsable.
 */
class CrecheAttendanceService
{
    public function __construct(private CrecheParentNotifier $notifier) {}

    public function today(Student $student): ?CrecheAttendance
    {
        return CrecheAttendance::where('student_id', $student->id)->whereDate('date', today())->first();
    }

    /**
     * Arrivée de l'enfant.
     *
     * @param  array{time?: ?string, dropped_off_by?: ?string, is_late?: bool, notes?: ?string}  $data
     */
    public function checkIn(Student $student, User $user, array $data = []): CrecheAttendance
    {
        $group = $this->groupOf($student);
        $attendance = $this->today($student);

        if ($attendance?->isOnSite()) {
            throw new DomainException("{$student->first_name} est déjà pointé(e) présent(e) depuis {$attendance->checked_in_at?->format('H:i')}.");
        }

        $arrivedAt = $this->timeToday($data['time'] ?? null);
        $attendance ??= new CrecheAttendance(['student_id' => $student->id, 'date' => today()->toDateString()]);
        $attendance->fill([
            'school_id' => $student->school_id,
            'school_class_id' => $group->id,
            'status' => 'present',
            'is_late' => (bool) ($data['is_late'] ?? false),
            'absence_reason' => null,
            'checked_in_at' => $arrivedAt,
            'checked_in_by' => $user->id,
            'dropped_off_by' => $data['dropped_off_by'] ?? null,
            'notes' => $data['notes'] ?? null,
            // Nouvelle arrivée après un départ dans la journée : le départ précédent reste au journal.
            'checked_out_at' => null,
            'checked_out_by' => null,
            'picked_up_by' => null,
            'pickup_person_id' => null,
            'verification_mode' => null,
            'late_pickup' => false,
            'marked_by' => $user->id,
        ]);
        $attendance->save();

        ActivityLog::audit(
            'creche.arrival',
            "Arrivée de {$student->first_name} {$student->last_name} à {$arrivedAt->format('H:i')}".($attendance->dropped_off_by ? " (accompagné(e) par {$attendance->dropped_off_by})" : ''),
            $student,
            ['attendance_id' => $attendance->id, 'time' => $arrivedAt->format('H:i'), 'dropped_off_by' => $attendance->dropped_off_by, 'is_late' => $attendance->is_late]
        );

        $this->notifier->arrival($student, $arrivedAt->format('H:i'));

        return $attendance;
    }

    /** Absence (justifiée ou non) : seulement si l'enfant n'est pas déjà arrivé aujourd'hui. */
    public function markAbsent(Student $student, User $user, bool $justified, ?string $reason = null): CrecheAttendance
    {
        $group = $this->groupOf($student);
        $attendance = $this->today($student);

        if ($attendance && in_array($attendance->status, ['present', 'left'], true)) {
            throw new DomainException("{$student->first_name} est déjà arrivé(e) aujourd'hui : l'absence ne peut pas être enregistrée.");
        }

        $attendance ??= new CrecheAttendance(['student_id' => $student->id, 'date' => today()->toDateString()]);
        $attendance->fill([
            'school_id' => $student->school_id,
            'school_class_id' => $group->id,
            'status' => $justified ? 'absent_justified' : 'absent',
            'absence_reason' => $reason,
            'is_late' => false,
            'marked_by' => $user->id,
        ]);
        $attendance->save();

        ActivityLog::audit(
            'creche.absence',
            ($justified ? 'Absence justifiée' : 'Absence')." de {$student->first_name} {$student->last_name}".($reason ? " : {$reason}" : ''),
            $student,
            ['attendance_id' => $attendance->id, 'justified' => $justified, 'reason' => $reason]
        );

        return $attendance;
    }

    /**
     * Vérifie si la personne peut récupérer l'enfant aujourd'hui, sans rien enregistrer.
     * Retourne null si elle est autorisée, sinon la raison du blocage.
     */
    public function refusalReason(Student $student, ?AuthorizedPickup $pickup): ?string
    {
        if (! $pickup) {
            return 'Cette personne ne figure pas dans la liste des personnes autorisées.';
        }
        if ($pickup->student_id !== $student->id || $pickup->trashed()) {
            return 'Cette personne a été retirée de la liste des personnes autorisées.';
        }
        if ($pickup->status === 'forbidden') {
            return 'Cette personne est INTERDITE de récupérer l\'enfant.';
        }
        if (! $pickup->isAuthorizedOn(today())) {
            return 'L\'autorisation de cette personne n\'est pas valable aujourd\'hui ('.mb_strtolower($pickup->stateLabel(today())).').';
        }

        return null;
    }

    /**
     * Départ de l'enfant avec la personne venue le chercher.
     *
     * @return array{allowed: bool, reason: ?string, attendance: ?CrecheAttendance}
     */
    public function checkOut(Student $student, User $user, ?int $pickupId, bool $identityChecked = false, ?string $otherPersonName = null): array
    {
        $attendance = $this->today($student);

        if (! $attendance || $attendance->isAbsent()) {
            throw new DomainException("{$student->first_name} n'est pas pointé(e) présent(e) aujourd'hui : enregistrez d'abord son arrivée.");
        }
        if ($attendance->status === 'left') {
            throw new DomainException("{$student->first_name} est déjà parti(e) à {$attendance->checked_out_at?->format('H:i')} avec {$attendance->picked_up_by}.");
        }

        $pickup = $pickupId
            ? AuthorizedPickup::withTrashed()->where('school_id', $student->school_id)->find($pickupId)
            : null;

        if ($reason = $this->refusalReason($student, $pickup)) {
            $who = $pickup?->full_name ?? (filled($otherPersonName) ? trim($otherPersonName) : 'personne non enregistrée');
            ActivityLog::audit(
                'creche.departure.blocked',
                "DÉPART BLOQUÉ pour {$student->first_name} {$student->last_name} : {$who}. {$reason}",
                $student,
                ['attendance_id' => $attendance->id, 'pickup_id' => $pickup?->id, 'person' => $who, 'reason' => $reason]
            );

            return ['allowed' => false, 'reason' => $reason, 'attendance' => $attendance];
        }

        $leftAt = now()->startOfMinute();
        $closing = $this->groupOf($student)->effectiveClosingTime();

        $attendance->fill([
            'status' => 'left',
            'checked_out_at' => $leftAt,
            'checked_out_by' => $user->id,
            'pickup_person_id' => $pickup->id,
            'picked_up_by' => "{$pickup->full_name} ({$pickup->relationship})",
            'verification_mode' => $identityChecked ? 'identity' : 'list',
            'late_pickup' => $closing !== '' && $leftAt->format('H:i') > $closing,
            'marked_by' => $user->id,
        ]);
        $attendance->save();

        ActivityLog::audit(
            'creche.departure',
            "Départ de {$student->first_name} {$student->last_name} à {$leftAt->format('H:i')} avec {$attendance->picked_up_by}".($attendance->late_pickup ? " — après la fermeture ({$closing})" : ''),
            $student,
            [
                'attendance_id' => $attendance->id, 'pickup_id' => $pickup->id, 'person' => $pickup->full_name,
                'verification_mode' => $attendance->verification_mode, 'late_pickup' => $attendance->late_pickup,
            ]
        );

        $this->notifier->departure($student, $leftAt->format('H:i'), $pickup->full_name);

        return ['allowed' => true, 'reason' => null, 'attendance' => $attendance];
    }

    /** Groupe de crèche de l'enfant (une présence crèche n'a de sens que dans un groupe). */
    public function groupOf(Student $student): SchoolClass
    {
        $group = $student->classes()->where('cycle', 'creche')->with('school')->first();

        if (! $group) {
            throw new DomainException("{$student->first_name} n'est inscrit(e) dans aucun groupe de crèche.");
        }

        return $group;
    }

    /** Heure saisie (HH:MM) aujourd'hui, ou maintenant ; jamais dans le futur. */
    private function timeToday(?string $time): CarbonInterface
    {
        $now = now()->startOfMinute();
        if (! $time || ! preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $now;
        }

        $at = today()->setTimeFromTimeString($time);

        return $at->gt($now) ? $now : $at;
    }
}
