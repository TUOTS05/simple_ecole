<?php

namespace App\Services;

use App\Models\CareDailySummary;
use App\Models\CareEvent;
use App\Models\CareIncident;
use App\Models\CrecheAttendance;
use App\Models\Student;
use Carbon\CarbonInterface;

/**
 * « La journée de mon enfant » : le carnet de vie d'une journée, tel qu'il est montré aux
 * parents (page web et application mobile). Construit à partir de la présence, du journal,
 * des incidents validés (si l'école le permet) et du résumé publié par l'auxiliaire.
 *
 * Volontairement limité à ce qui concerne le parent : pas de nom du personnel, pas de
 * commentaire interne de validation ni de suivi d'incident.
 */
class CareDayReport
{
    public function build(Student $student, CarbonInterface $date): array
    {
        $attendance = CrecheAttendance::where('student_id', $student->id)->whereDate('date', $date)->first();
        $events = CareEvent::where('student_id', $student->id)->whereDate('event_date', $date)->orderBy('start_time')->get();
        $summary = CareDailySummary::where('student_id', $student->id)->whereDate('summary_date', $date)->whereNotNull('published_at')->first();

        $byType = fn (string ...$types) => $events->whereIn('event_type', $types)->values();
        $sleeps = $byType('sleep');
        $bottles = $byType('bottle');

        return [
            'date' => $date->toDateString(),
            'presence' => [
                'status' => $attendance?->status,
                'status_label' => $attendance?->status_label,
                'arrived_at' => $attendance?->checked_in_at?->format('H:i'),
                'left_at' => $attendance?->checked_out_at?->format('H:i'),
                'picked_up_by' => $attendance?->checked_out_at ? $attendance->pickupPerson?->full_name ?? $attendance->picked_up_by : null,
                'absence_reason' => $attendance?->isAbsent() ? $attendance->absence_reason : null,
            ],
            'feeding' => [
                'bottles' => $bottles->map(fn (CareEvent $e) => $this->item($e))->all(),
                'bottles_total_ml' => (int) $bottles->sum(fn (CareEvent $e) => (float) ($e->datum('consumed') ?? $e->quantity ?? 0)),
                'meals' => $byType('meal')->map(fn (CareEvent $e) => $this->item($e))->all(),
                'snacks' => $byType('snack')->map(fn (CareEvent $e) => $this->item($e))->all(),
            ],
            'sleep' => [
                'total_minutes' => (int) $sleeps->sum(fn (CareEvent $e) => $e->durationMinutes() ?? 0),
                'total_label' => CareEvent::formatDuration((int) $sleeps->sum(fn (CareEvent $e) => $e->durationMinutes() ?? 0) ?: null),
                'sleeping_now' => $sleeps->contains(fn (CareEvent $e) => $e->isSleeping()),
                'naps' => $sleeps->map(fn (CareEvent $e) => $this->item($e) + ['end' => $e->endLabel()])->all(),
            ],
            'hygiene' => [
                'diapers' => $byType('diaper')->count(),
                'cares' => $byType('hygiene')->count(),
            ],
            'activities' => $byType('activity')->map(fn (CareEvent $e) => $this->item($e))->all(),
            'observations' => $byType('observation', 'other')->map(fn (CareEvent $e) => ['time' => $e->startLabel(), 'text' => $e->notes])->all(),
            'incidents' => $this->incidents($student, $date),
            'mood' => $summary?->mood ? ['code' => $summary->mood, 'label' => $summary->mood_label, 'icon' => $summary->mood_icon] : null,
            'message' => $summary?->message,
            'published' => (bool) $summary,
            'is_empty' => ! $attendance && $events->isEmpty() && ! $summary,
        ];
    }

    /** Incidents validés de la journée, si l'école a choisi de les montrer aux parents. */
    private function incidents(Student $student, CarbonInterface $date): array
    {
        if ($student->school?->setting('creche.incidents_parent_visibility') !== 'validated') {
            return [];
        }

        return CareIncident::where('student_id', $student->id)->whereDate('occurred_on', $date)
            ->where('status', 'validated')->orderBy('occurred_at')->get()
            ->map(fn (CareIncident $i) => [
                'time' => $i->timeLabel(),
                'type' => $i->type_label,
                'description' => $i->description,
                'action_taken' => $i->action_taken,
            ])->all();
    }

    private function item(CareEvent $event): array
    {
        return [
            'time' => $event->startLabel(),
            'type' => $event->event_type,
            'label' => $event->type_label,
            'summary' => $event->summary(),
            'notes' => $event->notes,
        ];
    }
}
