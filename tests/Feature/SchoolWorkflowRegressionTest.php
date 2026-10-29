<?php

namespace Tests\Feature;

use App\Models\CanteenRate;
use App\Models\Enrollment;
use App\Models\ReportCard;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Régressions trouvées en remplissant les formulaires sur une copie de la base : chacun de ces
 * parcours renvoyait une erreur au lieu d'enregistrer.
 */
class SchoolWorkflowRegressionTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private SchoolYear $year;

    private SchoolClass $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['name' => 'École A', 'slug' => 'a', 'status' => 'active', 'school_type' => 'primaire']);
        $this->admin = new User(['first_name' => 'Admin', 'last_name' => 'A', 'email' => 'admin@example.test', 'password' => bcrypt('password')]);
        $this->admin->role = 'school_admin';
        $this->admin->school_id = $this->school->id;
        $this->admin->save();

        $this->year = SchoolYear::create(['school_id' => $this->school->id, 'name' => '2026-2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-01', 'is_active' => true]);
        $this->class = SchoolClass::create([
            'school_id' => $this->school->id, 'name' => 'CP A', 'level' => 'CP', 'cycle' => 'primaire',
            'total_tuition' => 120000, 'registration_fee' => 20000, 'payment_modality' => 'trimestriel', 'number_of_installments' => 3,
        ]);
    }

    // L'inscription appelait une méthode qui n'existait plus dans ce contrôleur.
    public function test_enrollment_form_creates_student_parent_and_fee_schedule(): void
    {
        $this->actingAs($this->admin)->post(route('app.enrollments.store'), [
            'first_name' => 'Awa', 'last_name' => 'Koné', 'gender' => 'F', 'birth_date' => '2020-02-03', 'class_id' => $this->class->id,
            'status' => 'active', 'admission_date' => '2026-09-15', 'receipt_number' => 'R-001', 'guardian_type' => 'mother',
            'guardian_first_name' => 'Mariam', 'guardian_last_name' => 'Koné', 'guardian_phone' => '0700000001', 'guardian_email' => 'mariam@example.test',
        ])->assertSessionHasNoErrors();

        $enrollment = Enrollment::where('school_id', $this->school->id)->firstOrFail();
        $this->assertSame($this->class->id, $enrollment->school_class_id);
        $this->assertGreaterThan(0, $enrollment->studentInstallments()->count());
        $this->assertDatabaseHas('users', ['email' => 'mariam@example.test', 'role' => 'parent']);
    }

    // Sans bulletin du 3e trimestre, le bulletin de secours était créé sans auteur (colonne obligatoire).
    public function test_end_of_year_decision_without_third_term_report_card(): void
    {
        $student = new Student(['first_name' => 'Eleve', 'last_name' => 'A', 'birth_date' => '2018-01-01', 'gender' => 'M']);
        $student->school_id = $this->school->id;
        $student->save();
        $student->classes()->attach($this->class->id, ['school_year_id' => $this->year->id]);

        $this->actingAs($this->admin)
            ->post(route('app.end-of-year.update-decision', $student), ['decision' => 'admis'])
            ->assertSessionHasNoErrors();

        $card = ReportCard::where('student_id', $student->id)->where('quarter', 3)->firstOrFail();
        $this->assertSame('admis', $card->end_of_year_decision);
        $this->assertSame($this->admin->id, $card->created_by);
    }

    // Sans élève choisi, le PDF de la facture regroupée provoquait une erreur 500.
    public function test_consolidated_invoice_pdf_requires_a_student(): void
    {
        $this->actingAs($this->admin)
            ->get(route('extras.invoices.consolidated.pdf'))
            ->assertSessionHasErrors('student_id');
    }

    private function studentPayload(array $overrides = []): array
    {
        // Le formulaire envoie aussi tous les champs facultatifs, même vides.
        $optional = array_fill_keys(['religion', 'receipt_number', 'father_name', 'father_phone', 'father_occupation', 'mother_name',
            'mother_phone', 'mother_occupation', 'guardian_relation', 'guardian_occupation', 'guardian_address', 'current_address',
            'permanent_address', 'previous_school', 'remarks'], '');

        return array_merge($optional, [
            'first_name' => 'Awa', 'last_name' => 'Koné', 'gender' => 'F', 'birth_date' => '2020-02-03', 'class_id' => $this->class->id,
            'section' => 'A', 'status' => 'active', 'large_family' => 0, 'staff_child' => 0, 'admission_date' => '2026-09-15', 'guardian_type' => 'mother',
            'guardian_first_name' => 'Mariam', 'guardian_last_name' => 'Koné', 'guardian_phone' => '0700000001', 'guardian_email' => 'mariam@example.test',
        ], $overrides);
    }

    // Le formulaire d'admission rattachait l'élève à sa classe sans année : il disparaissait des listes par année.
    public function test_admission_form_assigns_the_class_for_the_active_year(): void
    {
        $this->actingAs($this->admin)->post(route('app.students.store'), $this->studentPayload())->assertSessionHasNoErrors();

        $student = Student::where('last_name', 'Koné')->firstOrFail();
        $this->assertDatabaseHas('student_school_class', ['student_id' => $student->id, 'school_class_id' => $this->class->id, 'school_year_id' => $this->year->id]);
        $this->assertDatabaseMissing('student_school_class', ['student_id' => $student->id, 'school_year_id' => null]);
    }

    // La modification d'un élève effaçait ses classes des années précédentes.
    public function test_student_update_keeps_classes_of_previous_years(): void
    {
        $this->actingAs($this->admin)->post(route('app.students.store'), $this->studentPayload())->assertSessionHasNoErrors();
        $student = Student::where('last_name', 'Koné')->firstOrFail();

        $previousYear = SchoolYear::create(['school_id' => $this->school->id, 'name' => '2025-2026', 'start_date' => '2025-09-01', 'end_date' => '2026-07-01', 'is_active' => false]);
        $student->classes()->attach($this->class->id, ['school_year_id' => $previousYear->id]);
        $ce1 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'CE1 A', 'level' => 'CE1', 'cycle' => 'primaire']);

        $this->actingAs($this->admin)->put(route('app.students.update', $student), $this->studentPayload(['class_id' => $ce1->id]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('student_school_class', ['student_id' => $student->id, 'school_class_id' => $this->class->id, 'school_year_id' => $previousYear->id]);
        $this->assertDatabaseHas('student_school_class', ['student_id' => $student->id, 'school_class_id' => $ce1->id, 'school_year_id' => $this->year->id]);
        $this->assertDatabaseMissing('student_school_class', ['student_id' => $student->id, 'school_class_id' => $this->class->id, 'school_year_id' => $this->year->id]);
    }

    // Les mois proposés à l'inscription à la cantine étaient une liste fixe (septembre 2026 à juin 2027).
    public function test_canteen_form_offers_the_months_of_the_class_rate(): void
    {
        CanteenRate::create([
            'school_id' => $this->school->id, 'school_year_id' => $this->year->id, 'school_class_id' => $this->class->id,
            'monthly_rate' => 10000, 'months_count' => 3, 'start_month' => '2026-09', 'end_month' => '2026-11',
        ]);

        $this->actingAs($this->admin)->get(route('canteen.subscriptions.index'))
            ->assertOk()
            ->assertViewHas('ratesByClass', function ($rates) {
                $rate = $rates[$this->class->id];

                return $rate['monthly_rate'] === 10000.0
                    && collect($rate['months'])->pluck('value')->all() === ['2026-09', '2026-10', '2026-11']
                    && $rate['months'][0]['label'] === 'Septembre 2026';
            });
    }
}
