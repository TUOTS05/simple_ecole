<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\StudentInstallment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Import d'une liste d'élèves : chaque ligne valide est inscrite comme une admission (classe de l'année
 * active, inscription, échéancier, compte parent si l'e-mail du tuteur est fourni). Les lignes en erreur
 * sont signalées sans bloquer les autres.
 */
class StudentImportService
{
    public function __construct(private GuardianAccountService $guardians) {}

    /**
     * @return array{imported: int, errors: list<string>, students: list<Student>}
     */
    public function import(Collection $rows, School $school, SchoolYear $year, ?int $defaultClassId, bool $notifyGuardians): array
    {
        $classes = SchoolClass::where('school_id', $school->id)->get()
            ->keyBy(fn ($class) => $this->normalize($class->name));
        $activeCount = Student::where('school_id', $school->id)->where('status', 'active')->count();

        $report = ['imported' => 0, 'errors' => [], 'students' => []];

        foreach ($rows as $index => $row) {
            $line = $index + 2; // ligne 1 = en-têtes
            $row = collect($row)->map(fn ($v) => is_string($v) ? trim($v) : $v);
            if ($row->filter(fn ($v) => $v !== null && $v !== '')->isEmpty()) {
                continue; // ligne vide
            }

            if ($school->max_students && $activeCount >= $school->max_students) {
                $report['errors'][] = "Ligne {$line} et suivantes : plafond de {$school->max_students} élèves de l'abonnement atteint.";
                break;
            }

            $error = null;
            $data = $this->parseRow($row, $classes, $defaultClassId, $error);
            if ($error) {
                $report['errors'][] = "Ligne {$line} : {$error}";

                continue;
            }

            $duplicate = Student::where('school_id', $school->id)
                ->where('last_name', $data['last_name'])->where('first_name', $data['first_name'])
                ->whereDate('birth_date', $data['birth_date'])->exists();
            if ($duplicate) {
                $report['errors'][] = "Ligne {$line} : {$data['last_name']} {$data['first_name']} est déjà inscrit(e), ligne ignorée.";

                continue;
            }

            if ($data['guardian_email'] && ($problem = $this->guardians->emailProblem($data['guardian_email']))) {
                $report['errors'][] = "Ligne {$line} : {$problem}";

                continue;
            }

            [$student, $link] = DB::transaction(fn () => $this->enroll($data, $school, $year));
            if ($link && $notifyGuardians) {
                $this->guardians->notify($link, $student, $school);
            }

            $report['imported']++;
            $report['students'][] = $student;
            $activeCount++;
        }

        return $report;
    }

    /**
     * Valide une ligne et la convertit ; $error reçoit le motif de rejet.
     */
    private function parseRow(Collection $row, Collection $classes, ?int $defaultClassId, ?string &$error): array
    {
        $lastName = (string) ($row['nom'] ?? '');
        $firstName = (string) ($row['prenom'] ?? '');
        if ($lastName === '' || $firstName === '') {
            $error = 'nom et prénom obligatoires.';

            return [];
        }

        $gender = strtoupper(substr((string) ($row['sexe'] ?? ''), 0, 1));
        if (! in_array($gender, ['M', 'F', 'G'], true)) {
            $error = 'sexe à indiquer par M ou F.';

            return [];
        }

        $birthDate = $this->parseDate($row['date_de_naissance'] ?? null);
        if (! $birthDate || $birthDate->isFuture()) {
            $error = 'date de naissance absente ou illisible (format attendu : JJ/MM/AAAA).';

            return [];
        }

        $className = (string) ($row['classe'] ?? '');
        $class = $className !== '' ? $classes->get($this->normalize($className)) : null;
        if ($className !== '' && ! $class) {
            $error = "classe « {$className} » inconnue dans l'école.";

            return [];
        }
        $classId = $class?->id ?? $defaultClassId;
        if (! $classId) {
            $error = 'classe absente (renseignez la colonne Classe ou choisissez une classe par défaut).';

            return [];
        }

        $email = strtolower((string) ($row['e_mail_du_tuteur'] ?? $row['email_du_tuteur'] ?? ''));
        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "e-mail du tuteur « {$email} » invalide.";

            return [];
        }

        $relation = $this->normalize((string) ($row['lien_du_tuteur'] ?? ''));

        return [
            'last_name' => $lastName,
            'first_name' => $firstName,
            'gender' => $gender === 'G' ? 'M' : $gender, // « G » pour garçon
            'birth_date' => $birthDate,
            'class_id' => $classId,
            'guardian_first_name' => (string) ($row['prenom_du_tuteur'] ?? ''),
            'guardian_last_name' => (string) ($row['nom_du_tuteur'] ?? $lastName),
            'guardian_phone' => (string) ($row['telephone_du_tuteur'] ?? ''),
            'guardian_email' => $email ?: null,
            'guardian_type' => match ($relation) {
                'pere' => 'father',
                'mere' => 'mother',
                default => 'other',
            },
        ];
    }

    /**
     * Crée l'élève, son compte parent, son inscription et son échéancier.
     *
     * @return array{0: Student, 1: ?array}
     */
    private function enroll(array $data, School $school, SchoolYear $year): array
    {
        $student = new Student([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'gender' => $data['gender'],
            'birth_date' => $data['birth_date'],
            'status' => 'active',
            'admission_date' => today(),
            'guardian_type' => $data['guardian_type'],
            'guardian_name' => trim($data['guardian_first_name'].' '.$data['guardian_last_name']),
            'guardian_phone' => $data['guardian_phone'],
            'guardian_email' => $data['guardian_email'],
        ]);
        $student->school_id = $school->id;
        $student->save();

        $link = $data['guardian_email']
            ? $this->guardians->link($student, $data, $school->id)
            : null;

        $enrollment = Enrollment::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'school_year_id' => $year->id,
            'school_class_id' => $data['class_id'],
            'enrollment_date' => today(),
            'status' => 'enrolled',
        ]);
        $student->classes()->attach($data['class_id'], ['school_year_id' => $year->id]);

        $class = SchoolClass::find($data['class_id']);
        if ($class && $class->total_tuition > 0) {
            StudentInstallment::generateScheduleFor($enrollment, $class, $enrollment->enrollment_date);
        }

        return [$student, $link];
    }

    /**
     * Date saisie dans le tableur : nombre de jours Excel, JJ/MM/AAAA ou AAAA-MM-JJ.
     */
    private function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        $value = trim((string) $value);
        if (preg_match('#^(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})$#', $value, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            return Carbon::create((int) $m[3], (int) $m[2], (int) $m[1]);
        }
        if (preg_match('#^(\d{4})-(\d{2})-(\d{2})$#', $value, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return Carbon::create((int) $m[1], (int) $m[2], (int) $m[3]);
        }

        return null;
    }

    /**
     * Forme comparable d'un libellé : minuscules, sans accents ni espaces superflus.
     */
    private function normalize(string $value): string
    {
        return Str::of($value)->ascii()->lower()->squish()->toString();
    }
}
