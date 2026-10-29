<?php

namespace App\Exports;

use App\Models\Student;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

// Optionnel : pour nommer l'onglet Excel

/**
 * Export Excel de la liste des élèves d'une école, éventuellement d'une seule classe.
 */
class StudentsExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $schoolId;

    protected $classId;

    /**
     * École et classe (facultative) à exporter.
     */
    public function __construct($schoolId, $classId = null)
    {
        $this->schoolId = $schoolId;
        $this->classId = $classId;
    }

    /**
     * Élèves de l'école, filtrés par classe si demandé.
     */
    public function collection()
    {
        // 'mother_phone' est sélectionné pour être disponible dans map()
        $query = Student::where('school_id', $this->schoolId)
            ->select('id', 'matricule', 'first_name', 'last_name', 'gender', 'birth_date', 'guardian_phone', 'father_phone', 'mother_phone', 'mother_name');

        // FILTRE PAR CLASSE : On joint la table enrollments si une classe est spécifiée
        if ($this->classId) {
            $query->whereHas('enrollments', function ($q) {
                $q->where('school_class_id', $this->classId);
            });

        }

        return $query->orderBy('last_name')->orderBy('first_name')->get();
    }

    /**
     * En-têtes des colonnes.
     */
    public function headings(): array
    {
        return [
            'Matricule',
            'Nom',
            'Prénom',
            'Genre',
            'Date de naissance',
            'Tél. Responsable',
            'Nom de la Mère',
        ];
    }

    /**
     * Une ligne par élève ; le téléphone est celui du tuteur, à défaut du père, puis de la mère.
     */
    public function map($student): array
    {
        // On prend le téléphone du tuteur, sinon celui du père, sinon celui de la mère
        $phone = $student->guardian_phone ?: ($student->father_phone ?: ($student->mother_phone ?? 'N/A'));

        return [
            $student->matricule ?? 'N/A',
            strtoupper($student->last_name ?? 'N/A'),
            ucfirst($student->first_name ?? 'N/A'),
            $student->gender === 'M' ? 'Masculin' : ($student->gender === 'F' ? 'Féminin' : 'N/A'),
            $student->birth_date ? Carbon::parse($student->birth_date)->format('d/m/Y') : 'N/A',
            $phone,
            $student->mother_name ?? 'N/A',
        ];
    }

    // Optionnel : Donne un nom propre à l'onglet du fichier Excel
    public function title(): string
    {
        return 'Liste Élèves';
    }
}
