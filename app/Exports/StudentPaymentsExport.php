<?php

namespace App\Exports;

use App\Models\Enrollment;
use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentPaymentsExport implements FromCollection, WithHeadings, WithMapping
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

        return Payment::where('enrollment_id', $enrollment->id)
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return ['Date Paiement', 'Montant (FCFA)', 'Type de Paiement', 'Mode de Paiement', 'Référence', 'Notes'];
    }

    public function map($row): array
    {
        return [
            \Carbon\Carbon::parse($row->payment_date)->format('d/m/Y'),
            $row->amount,
            $row->payment_type ?? '-',
            $row->payment_method ?? '-',
            $row->reference ?? '-',
            $row->notes ?? '-'
        ];
    }
}