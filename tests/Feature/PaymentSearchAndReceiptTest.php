<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\StudentInstallment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Retours du cahier de test (paiements) : recherche d'un élève par matricule pour encaisser, et reçu
 * toujours accessible après le paiement (le lien /storage était cassé sans « storage:link »).
 */
class PaymentSearchAndReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function context(string $slug): array
    {
        $school = School::create(['name' => "École {$slug}", 'slug' => $slug, 'status' => 'active']);
        $admin = new User(['first_name' => 'Admin', 'last_name' => $slug, 'email' => "admin-{$slug}@example.test", 'password' => bcrypt('password')]);
        $admin->role = 'school_admin';
        $admin->school_id = $school->id;
        $admin->save();

        $year = SchoolYear::create(['school_id' => $school->id, 'name' => '2026-2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-01', 'is_active' => true]);
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'CP A', 'level' => 'CP', 'cycle' => 'primaire']);
        $student = new Student(['first_name' => 'Awa', 'last_name' => 'Koné', 'birth_date' => '2019-01-01', 'gender' => 'F', 'matricule' => "2026-{$slug}-001"]);
        $student->school_id = $school->id;
        $student->save();
        $enrollment = Enrollment::create(['student_id' => $student->id, 'school_id' => $school->id, 'school_year_id' => $year->id, 'school_class_id' => $class->id, 'status' => 'enrolled', 'enrollment_date' => '2026-09-01']);
        $installment = StudentInstallment::create(['school_id' => $school->id, 'enrollment_id' => $enrollment->id, 'type' => 'installment', 'description' => 'Tranche 1', 'amount' => 50000, 'due_date' => '2026-10-01']);

        return compact('school', 'admin', 'year', 'student', 'enrollment', 'installment');
    }

    public function test_search_finds_a_student_by_matricule_or_name_within_the_school(): void
    {
        $a = $this->context('AAA');
        $this->context('BBB');

        $byMatricule = $this->actingAs($a['admin'])->getJson(route('app.payments.search-students', ['q' => '2026-AAA', 'year_id' => $a['year']->id]))->assertOk()->json();
        $this->assertCount(1, $byMatricule);
        $this->assertSame($a['enrollment']->id, $byMatricule[0]['enrollment_id']);
        $this->assertSame($a['installment']->id, $byMatricule[0]['installments'][0]['id']);

        // « koné awa » : chaque mot dans le nom ou le prénom ; l'élève de l'autre école n'apparaît pas.
        $byName = $this->actingAs($a['admin'])->getJson(route('app.payments.search-students', ['q' => 'koné awa', 'year_id' => $a['year']->id]))->assertOk()->json();
        $this->assertSame([$a['student']->id], array_column($byName, 'id'));
    }

    public function test_payment_redirects_to_a_receipt_served_by_the_application(): void
    {
        $a = $this->context('AAA');

        $this->actingAs($a['admin'])->post(route('app.payments.store'), [
            'student_id' => $a['student']->id, 'enrollment_id' => $a['enrollment']->id, 'student_installment_id' => $a['installment']->id,
            'amount' => 20000, 'payment_date' => '2026-10-02', 'payment_method' => 'cash', 'payment_type' => 'tuition',
        ])->assertRedirect(route('app.payments.index'));

        $payment = Payment::firstOrFail();
        $this->assertSame(route('app.payments.receipt', $payment), session('payment_success.receipt_url'));

        $this->actingAs($a['admin'])->get(route('app.payments.receipt', $payment))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
