<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Export Excel du dossier financier d'un élève, en trois feuilles.
 */
class StudentDetailExport implements WithMultipleSheets
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
     * Feuilles du classeur : résumé, échéances et paiements.
     */
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
