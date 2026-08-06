<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $schoolId;
    protected $classId;

    public function __construct($schoolId, $classId = null)
    {
        $this->schoolId = $schoolId;
        $this->classId = $classId;
    }

    public function collection()
    {
        $query = Student::where('school_id', $this->schoolId)
            ->select('matricule', 'first_name', 'last_name', 'gender', 'birth_date', 'guardian_phone', 'father_phone', 'mother_name');

        // ✅ FILTRE PAR CLASSE : On joint la table enrollments si une classe est spécifiée
        if ($this->classId) {
            $query->whereHas('enrollments', function ($q) {
                $q->where('school_class_id', $this->classId);
            });
        }

        return $query->orderBy('last_name')->orderBy('first_name')->get();
    }

    public function headings(): array
    {
        return ['Matricule', 'Nom', 'Prénom', 'Genre', 'Date de naissance', 'Tél. Responsable', 'Nom de la Mère'];
    }

    public function map($student): array
    {
        // On prend le téléphone du tuteur, sinon celui du père, sinon celui de la mère
        $phone = $student->guardian_phone ?: ($student->father_phone ?: ($student->mother_phone ?? 'N/A'));

        return [
            $student->matricule ?? 'N/A',
            strtoupper($student->last_name),
            ucfirst($student->first_name),
            $student->gender === 'M' ? 'Masculin' : 'Féminin',
            $student->birth_date ? \Carbon\Carbon::parse($student->birth_date)->format('d/m/Y') : 'N/A',
            $phone,
            $student->mother_name ?? 'N/A',
        ];
    }
}