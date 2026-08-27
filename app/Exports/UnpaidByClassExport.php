<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class UnpaidByClassExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $schoolId;

    protected $schoolYearId;

    public function __construct($schoolId, $schoolYearId)
    {
        $this->schoolId = $schoolId;
        $this->schoolYearId = $schoolYearId;
    }

    public function collection()
    {
        return DB::table('school_classes')
            ->select(
                'school_classes.id as class_id',
                'school_classes.name as class_name',
                DB::raw('COUNT(DISTINCT students.id) as total_students'),
                DB::raw('COALESCE(SUM(student_installments.amount), 0) as total_expected'),
                DB::raw('COALESCE(SUM(student_installments.paid_amount), 0) as total_paid'),
                DB::raw('COALESCE(SUM(student_installments.amount), 0) - COALESCE(SUM(student_installments.paid_amount), 0) as total_unpaid')
            )
            ->join('enrollments', 'school_classes.id', '=', 'enrollments.school_class_id')
            ->join('students', 'enrollments.student_id', '=', 'students.id')
            ->leftJoin('student_installments', 'enrollments.id', '=', 'student_installments.enrollment_id')
            ->where('enrollments.school_id', $this->schoolId)
            ->where('enrollments.school_year_id', $this->schoolYearId)
            ->groupBy('school_classes.id', 'school_classes.name')
            ->orderBy('school_classes.name')
            ->get()
            ->map(function ($class) {
                $class->recovery_rate = $class->total_expected > 0
                    ? round(($class->total_paid / $class->total_expected) * 100, 1)
                    : 0;

                return $class;
            });
    }

    public function headings(): array
    {
        return [
            'Classe',
            'Nombre d\'élèves',
            'Total Attendu (FCFA)',
            'Total Payé (FCFA)',
            'Total Impayé (FCFA)',
            'Taux de Recouvrement (%)',
        ];
    }

    public function map($row): array
    {
        return [
            $row->class_name,
            $row->total_students,
            $row->total_expected,
            $row->total_paid,
            $row->total_unpaid,
            $row->recovery_rate,
        ];
    }

    public function title(): string
    {
        return 'Impayés par classe';
    }
}
