<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class ClassDetailExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $classId;

    protected $schoolYearId;

    protected $schoolId;

    public function __construct($classId, $schoolYearId, $schoolId = null)
    {
        $this->classId = $classId;
        $this->schoolYearId = $schoolYearId;
        $this->schoolId = $schoolId;
    }

    public function collection()
    {
        return DB::table('students')
            ->select(
                'students.id as student_id',
                'students.matricule',
                DB::raw("CONCAT(students.last_name, ' ', students.first_name) as full_name"),
                DB::raw('COALESCE(SUM(student_installments.amount), 0) as total_du'),
                DB::raw('COALESCE(SUM(student_installments.paid_amount), 0) as total_paye'),
                DB::raw('COALESCE(SUM(student_installments.amount), 0) - COALESCE(SUM(student_installments.paid_amount), 0) as total_reste')
            )
            ->join('enrollments', 'students.id', '=', 'enrollments.student_id')
            ->leftJoin('student_installments', 'enrollments.id', '=', 'student_installments.enrollment_id')
            ->when($this->schoolId, fn ($query) => $query->where('enrollments.school_id', $this->schoolId))
            ->where('enrollments.school_class_id', $this->classId)
            ->where('enrollments.school_year_id', $this->schoolYearId)
            ->groupBy('students.id', 'students.matricule', 'students.first_name', 'students.last_name')
            ->orderBy('students.last_name')
            ->orderBy('students.first_name')
            ->get()
            ->map(function ($student) {
                $total = (float) $student->total_du;
                $paye = (float) $student->total_paye;
                $student->payment_rate = $total > 0 ? round(($paye / $total) * 100, 1) : 0;

                return $student;
            });
    }

    public function headings(): array
    {
        return [
            'Matricule',
            'Nom et Prénom',
            'Total Dû (FCFA)',
            'Total Payé (FCFA)',
            'Reste à Payer (FCFA)',
            'Taux de Paiement (%)',
        ];
    }

    public function map($row): array
    {
        return [
            $row->matricule ?? 'N/A',
            $row->full_name,
            $row->total_du,
            $row->total_paye,
            $row->total_reste,
            $row->payment_rate,
        ];
    }

    public function title(): string
    {
        return 'Détail des paiements';
    }
}
