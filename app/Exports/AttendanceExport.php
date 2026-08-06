<?php

namespace App\Exports;

use App\Models\Attendance;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping
{
    protected $classId;
    protected $startDate;
    protected $endDate;
    protected $isTeacher;
    protected $assignedClassIds;

    public function __construct($classId, $startDate, $endDate, $isTeacher = false, $assignedClassIds = [])
    {
        $this->classId = $classId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->isTeacher = $isTeacher;
        $this->assignedClassIds = $assignedClassIds;
    }

    public function collection()
    {
        $query = Attendance::query()
            ->select(
                'attendances.date',
                'attendances.period',
                'school_classes.name as class_name',
                'students.matricule',
                DB::raw("CONCAT(students.last_name, ' ', students.first_name) as student_name"),
                'attendances.status',
                'attendances.notes'
            )
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->join('school_classes', 'attendances.school_class_id', '=', 'school_classes.id')
            ->whereBetween('attendances.date', [$this->startDate, $this->endDate])
            ->orderBy('attendances.date', 'desc');

        // Sécurité : si c'est un enseignant, on limite à SES classes
        if ($this->isTeacher && !empty($this->assignedClassIds)) {
            $query->whereIn('attendances.school_class_id', $this->assignedClassIds);
        }

        // Filtre par classe spécifique si demandé
        if ($this->classId) {
            $query->where('attendances.school_class_id', $this->classId);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return ['Date', 'Période', 'Classe', 'Matricule', 'Élève', 'Statut', 'Observation'];
    }

    public function map($row): array
    {
        $statusLabels = [
            'present' => 'Présent',
            'absent' => 'Absent',
            'late' => 'Retard',
            'excused' => 'Excusé'
        ];

        return [
            \Carbon\Carbon::parse($row->date)->format('d/m/Y'),
            $row->period === 'matin' ? 'Matin' : 'Après-midi',
            $row->class_name,
            $row->matricule,
            $row->student_name,
            $statusLabels[$row->status] ?? $row->status,
            $row->notes ?: '-'
        ];
    }
}