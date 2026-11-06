<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\ReportCard;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\StudentInstallment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Retours du cahier de test (espace parent) : le solde du tableau de bord doit être celui de la page
 * Paiements, et la page des bulletins annonce le calendrier de publication.
 */
class ParentSpaceConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    private Student $student;

    private SchoolYear $year;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2027-01-15');

        $school = School::create(['name' => 'École A', 'slug' => 'a', 'status' => 'active']);
        $this->year = SchoolYear::create(['school_id' => $school->id, 'name' => '2026-2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_active' => true]);
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'CP A', 'level' => 'CP', 'cycle' => 'primaire']);

        $this->student = new Student(['first_name' => 'Awa', 'last_name' => 'Koné', 'birth_date' => '2019-01-01', 'gender' => 'F']);
        $this->student->school_id = $school->id;
        $this->student->save();

        $this->parent = new User(['first_name' => 'Mariam', 'last_name' => 'Koné', 'email' => 'mariam@example.test', 'password' => bcrypt('password')]);
        $this->parent->role = 'parent';
        $this->parent->school_id = $school->id;
        $this->parent->save();
        $this->parent->children()->attach($this->student->id, ['school_id' => $school->id]);

        $enrollment = Enrollment::create(['student_id' => $this->student->id, 'school_id' => $school->id, 'school_year_id' => $this->year->id, 'school_class_id' => $class->id, 'status' => 'enrolled', 'enrollment_date' => '2026-09-01']);
        // Frais d'inscription (payés) + une tranche de scolarité (partiellement payée)
        StudentInstallment::create(['school_id' => $school->id, 'enrollment_id' => $enrollment->id, 'type' => 'registration', 'description' => 'Inscription', 'amount' => 20000, 'paid_amount' => 20000, 'status' => 'paid', 'due_date' => '2026-09-01']);
        StudentInstallment::create(['school_id' => $school->id, 'enrollment_id' => $enrollment->id, 'type' => 'installment', 'description' => 'Tranche 1', 'amount' => 100000, 'paid_amount' => 40000, 'status' => 'partial', 'due_date' => '2026-12-01']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_dashboard_and_payments_page_show_the_same_balance(): void
    {
        $dashboard = $this->actingAs($this->parent)->get(route('parent.dashboard'))->assertOk();
        $this->assertSame(120000.0, (float) $dashboard->viewData('globalStats')['totalFees']);
        $this->assertSame(60000.0, (float) $dashboard->viewData('globalStats')['totalPaid']);

        $payments = $this->actingAs($this->parent)->get(route('parent.payments.index', $this->student))->assertOk();
        $this->assertSame(120000.0, (float) $payments->viewData('totalExpected'));
        $this->assertSame(60000.0, (float) $payments->viewData('totalPaid'));
    }

    public function test_report_card_page_shows_the_publication_schedule(): void
    {
        ReportCard::create([
            'student_id' => $this->student->id, 'school_id' => $this->student->school_id, 'school_year_id' => $this->year->id,
            'school_class_id' => SchoolClass::first()->id, 'period' => 'Trimestriel', 'quarter' => 1, 'average' => 14, 'created_by' => $this->parent->id,
        ]);

        $schedule = $this->actingAs($this->parent)->get(route('parent.grades.index', $this->student))->assertOk()->viewData('schedule');

        $this->assertSame(['disponible', 'prévu', 'prévu'], array_column($schedule, 'status'));
        $this->assertSame('1er trimestre', $schedule[0]['label']);
    }
}
