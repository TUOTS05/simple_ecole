<?php

namespace App\Exports;

use App\Models\Enrollment;
use App\Models\StudentInstallment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentInstallmentsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $studentId;
    protected $schoolYearId;

    public function __construct($studentId, $schoolYearId)
    {
        $this->studentId = $studentId;
        $this->schoolYearId = $schoolYearId;
    }

    public function collection()
    {
        $enrollment = Enrollment::where('student_id', $this->studentId)
            ->where('school_year_id', $this->schoolYearId)
            ->first();

        return StudentInstallment::where('enrollment_id', $enrollment->id)
            ->orderBy('due_date')
            ->get();
    }

    public function headings(): array
    {
        return ['Type', 'Description', 'Montant (FCFA)', 'Montant Payé (FCFA)', 'Date Échéance', 'Statut'];
    }

    public function map($row): array
    {
        return [
            $row->type,
            $row->description ?? '-',
            $row->amount,
            $row->paid_amount,
            $row->due_date->format('d/m/Y'),
            ucfirst($row->status)
        ];
    }
}