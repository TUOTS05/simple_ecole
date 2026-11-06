<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Modèle Excel à remplir pour importer une liste d'élèves (une ligne d'exemple).
 */
class StudentImportTemplate implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * En-têtes reconnus par StudentImportService (les accents et majuscules sont ignorés).
     */
    public function headings(): array
    {
        return ['Nom', 'Prénom', 'Sexe', 'Date de naissance', 'Classe', 'Prénom du tuteur', 'Nom du tuteur', 'Téléphone du tuteur', 'E-mail du tuteur', 'Lien du tuteur'];
    }

    /**
     * Ligne d'exemple, à remplacer par les élèves.
     */
    public function array(): array
    {
        return [['KONÉ', 'Awa', 'F', '03/02/2019', 'CP1 A', 'Mariam', 'KONÉ', '0700000000', 'mariam.kone@exemple.ci', 'mère']];
    }
}
