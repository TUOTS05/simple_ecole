<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Lit la première feuille du fichier importé ; le traitement des lignes est fait par StudentImportService.
 */
class StudentRowsImport implements ToCollection, WithHeadingRow
{
    public Collection $rows;

    /**
     * Conserve les lignes, indexées par les en-têtes de colonnes.
     */
    public function collection(Collection $rows): void
    {
        $this->rows = $rows;
    }
}
