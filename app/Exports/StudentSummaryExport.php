<?php

namespace App\Exports;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\StudentInstallment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Feuille de résumé financier d'un élève pour une année.
 */
class StudentSummaryExport implements FromCollection, WithHeadings, WithMapping
{
    protected $studentId;

    protected $schoolYearId;

    /**
     * Élève et année concernés.
     */
    public function __construct($studentId, $schoolYearId)
    {
        $this->studentId = $studentId;
        $this->schoolYearId = $schoolYearId;
    }

    /**
     * Une ligne : élève, classe et totaux dus, payés et restants.
     */
    public function collection()
    {
        $student = Student::find($this->studentId);
        $enrollment = Enrollment::where('student_id', $this->studentId)
            ->where('school_year_id', $this->schoolYearId)
            ->first();

        $installments = StudentInstallment::where('enrollment_id', $enrollment->id)->get();
        $totalDue = $installments->sum('amount');
        $totalPaid = $installments->sum('paid_amount');

        return collect([
            (object) [
                'matricule' => $student->matricule,
                'nom' => $student->last_name.' '.$student->first_name,
                'classe' => $enrollment->schoolClass->name ?? 'N/A',
                'total_du' => $totalDue,
                'total_paye' => $totalPaid,
                'reste' => $totalDue - $totalPaid,
                'taux' => $totalDue > 0 ? round(($totalPaid / $totalDue) * 100, 1) : 0,
            ],
        ]);
    }

    public function headings(): array
    {
        return ['Matricule', 'Nom et Prénom', 'Classe', 'Total Dû (FCFA)', 'Total Payé (FCFA)', 'Reste (FCFA)', 'Taux (%)'];
    }

    /**
     * Ligne de résumé de l'élève.
     */
    public function map($row): array
    {
        return [
            $row->matricule,
            $row->nom,
            $row->classe,
            $row->total_du,
            $row->total_paye,
            $row->reste,
            $row->taux,
        ];
    }
}
