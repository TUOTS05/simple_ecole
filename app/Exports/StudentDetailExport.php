<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StudentDetailExport implements WithMultipleSheets
{
    protected $studentId;

    protected $schoolYearId;

    public function __construct($studentId, $schoolYearId)
    {
        $this->studentId = $studentId;
        $this->schoolYearId = $schoolYearId;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Feuille 1 : Résumé
        $sheets[] = new StudentSummaryExport($this->studentId, $this->schoolYearId);

        // Feuille 2 : Échéances
        $sheets[] = new StudentInstallmentsExport($this->studentId, $this->schoolYearId);

        // Feuille 3 : Paiements
        $sheets[] = new StudentPaymentsExport($this->studentId, $this->schoolYearId);

        return $sheets;
    }
}
