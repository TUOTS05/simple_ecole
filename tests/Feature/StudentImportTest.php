<?php

namespace Tests\Feature;

use App\Mail\ParentWelcomeMail;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Retour KO du cahier de test : import d'une liste d'élèves.
 */
class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private SchoolYear $year;

    private SchoolClass $class;

    protected function setUp(): void
    {
        parent::setUp();

        $school = School::create(['name' => 'École A', 'slug' => 'a', 'status' => 'active']);
        $this->admin = new User(['first_name' => 'Admin', 'last_name' => 'A', 'email' => 'admin@example.test', 'password' => bcrypt('password')]);
        $this->admin->role = 'school_admin';
        $this->admin->school_id = $school->id;
        $this->admin->save();

        $this->year = SchoolYear::create(['school_id' => $school->id, 'name' => '2026-2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-01', 'is_active' => true]);
        $this->class = SchoolClass::create([
            'school_id' => $school->id, 'name' => 'CP1 A', 'level' => 'CP', 'cycle' => 'primaire',
            'total_tuition' => 90000, 'registration_fee' => 10000, 'payment_modality' => 'trimestriel', 'number_of_installments' => 3,
        ]);
    }

    private function csv(array $lines): UploadedFile
    {
        $header = 'Nom,Prénom,Sexe,Date de naissance,Classe,Prénom du tuteur,Nom du tuteur,Téléphone du tuteur,E-mail du tuteur,Lien du tuteur';

        return UploadedFile::fake()->createWithContent('eleves.csv', $header."\n".implode("\n", $lines)."\n");
    }

    public function test_valid_rows_are_enrolled_and_invalid_rows_are_reported(): void
    {
        Mail::fake();

        $this->actingAs($this->admin)->post(route('app.students.import.store'), [
            'file' => $this->csv([
                'KONÉ,Awa,F,3/2/2019,cp1 a,Mariam,KONÉ,0700000001,mariam@example.test,mère',
                'TRAORÉ,Moussa,M,2018-11-20,CP1 A,,,,,',
                'DIALLO,Inès,F,03/02/2019,CE2 Z,,,,,',
                'BAMBA,Yao,X,03/02/2019,CP1 A,,,,,',
                'COULIBALY,Sali,F,31/02/2019,CP1 A,,,,,',
                ',,,,,,,,,',
            ]),
            'notify_guardians' => 1,
        ])->assertRedirect(route('app.students.import'));

        $report = session('import_report');
        $this->assertSame(2, $report['imported']);
        $this->assertCount(3, $report['errors']);
        $this->assertStringContainsString('Ligne 4 : classe « CE2 Z » inconnue', $report['errors'][0]);
        $this->assertStringContainsString('Ligne 5 : sexe', $report['errors'][1]);
        $this->assertStringContainsString('Ligne 6 : date de naissance', $report['errors'][2]);

        $awa = Student::where('first_name', 'Awa')->firstOrFail();
        $this->assertSame('2019-02-03', $awa->birth_date->format('Y-m-d'));
        $this->assertDatabaseHas('student_school_class', ['student_id' => $awa->id, 'school_class_id' => $this->class->id, 'school_year_id' => $this->year->id]);
        $this->assertGreaterThan(0, $awa->enrollments()->first()->studentInstallments()->count());
        $this->assertDatabaseHas('users', ['email' => 'mariam@example.test', 'role' => 'parent']);
        Mail::assertSent(ParentWelcomeMail::class, 1);
    }

    public function test_reimporting_the_same_file_skips_students_already_enrolled(): void
    {
        $file = fn () => $this->csv(['KONÉ,Awa,F,03/02/2019,CP1 A,,,,,']);

        $this->actingAs($this->admin)->post(route('app.students.import.store'), ['file' => $file()]);
        $this->actingAs($this->admin)->post(route('app.students.import.store'), ['file' => $file()]);

        $this->assertSame(1, Student::count());
        $this->assertStringContainsString('déjà inscrit', session('import_report')['errors'][0]);
    }

    public function test_template_is_downloadable(): void
    {
        $this->actingAs($this->admin)->get(route('app.students.import.template'))
            ->assertOk()
            ->assertDownload('modele_import_eleves.xlsx');
    }
}
