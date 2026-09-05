<?php

namespace App\Services;

use App\Models\ReportCard;
use App\Models\Student;

class StudentProgressionService
{
    /**
     * Historique chronologique de la moyenne générale d'un élève, tous bulletins confondus
     * (compositions mensuelles ou trimestrielles) et toutes années scolaires confondues.
     * Sert à tracer une courbe de progression sur le dashboard/la fiche parent.
     *
     * @return array{labels: array<int, string>, averages: array<int, float>, ranks: array<int, ?int>, totalStudents: array<int, ?int>}
     */
    public function forStudent(Student $student, ?int $limit = null): array
    {
        $reportCards = ReportCard::where('student_id', $student->id)
            ->whereNotNull('average')
            ->with('schoolYear')
            ->get()
            ->filter(fn (ReportCard $reportCard) => $reportCard->schoolYear !== null)
            ->sortBy(fn (ReportCard $reportCard) => $this->sortKey($reportCard))
            ->values();

        if ($limit) {
            $reportCards = $reportCards->slice(-$limit)->values();
        }

        return [
            'labels' => $reportCards->map(fn (ReportCard $reportCard) => $this->label($reportCard))->all(),
            'averages' => $reportCards->map(fn (ReportCard $reportCard) => round((float) $reportCard->average, 2))->all(),
            'ranks' => $reportCards->map(fn (ReportCard $reportCard) => $reportCard->rank)->all(),
            'totalStudents' => $reportCards->map(fn (ReportCard $reportCard) => $reportCard->total_students)->all(),
        ];
    }

    /**
     * Clé de tri chronologique : année scolaire d'abord, puis position de la composition dans
     * l'année (le mois ou le trimestre), qu'on ramène sur une échelle commune 1-12 pour que
     * mensuel et trimestriel s'intercalent dans le bon ordre si une école mélange les deux.
     */
    private function sortKey(ReportCard $reportCard): string
    {
        $startDate = $reportCard->schoolYear->start_date?->format('Y-m-d') ?? '0000-00-00';

        return $startDate.'-'.str_pad((string) $this->periodPosition($reportCard), 2, '0', STR_PAD_LEFT);
    }

    private function periodPosition(ReportCard $reportCard): int
    {
        if (strtolower($reportCard->period ?? '') === 'trimestriel') {
            return match ((int) $reportCard->quarter) {
                1 => 2, 2 => 6, 3 => 10, default => 0,
            };
        }

        $order = [
            'septembre' => 1, 'octobre' => 2, 'novembre' => 3, 'décembre' => 4,
            'janvier' => 5, 'février' => 6, 'mars' => 7, 'avril' => 8,
            'mai' => 9, 'juin' => 10, 'juillet' => 11, 'août' => 12,
        ];

        return $order[mb_strtolower((string) $reportCard->month)] ?? 0;
    }

    private function label(ReportCard $reportCard): string
    {
        $year = $reportCard->schoolYear->name ?? '';

        if (strtolower($reportCard->period ?? '') === 'trimestriel') {
            $quarterLabel = match ((int) $reportCard->quarter) {
                1 => '1er trim.', 2 => '2e trim.', 3 => '3e trim.', default => 'Trim.',
            };

            return trim($year.' '.$quarterLabel);
        }

        return trim($year.' '.($reportCard->month ?? 'Composition'));
    }
}
