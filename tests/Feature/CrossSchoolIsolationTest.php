<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\StaffFunction;
use App\Models\Student;
use App\Models\StudentInstallment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Verrouille le cloisonnement multi-écoles corrigé le 24/08/2026 : un utilisateur
 * authentifié dans l'école A ne doit jamais pouvoir lire ou modifier une ressource
 * (paiement, échéance, élève, classe) appartenant à l'école B en devinant/forgeant
 * un ID dans la requête (IDOR).
 */
class CrossSchoolIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function makeSchool(string $slug): School
    {
        return School::create([
            'name' => 'École '.$slug,
            'slug' => $slug,
            'status' => 'active',
        ]);
    }

    private function makeAccountant(School $school): User
    {
        // role/school_id ne sont volontairement plus mass-assignables sur User
        // (durcissement anti élévation de privilège) : affectation directe.
        $user = new User([
            'first_name' => 'Compta',
            'last_name' => $school->slug,
            'email' => 'compta-'.$school->slug.'@example.test',
            'password' => bcrypt('password'),
        ]);
        // Le comptable est un membre du personnel dont la fonction ouvre inscriptions et paiements
        $function = new StaffFunction(['name' => 'Comptable', 'permissions' => ['enrollments', 'payments', 'extras_billing']]);
        $function->school_id = $school->id;
        $function->save();

        $user->role = 'staff';
        $user->school_id = $school->id;
        $user->staff_function_id = $function->id;
        $user->save();

        return $user;
    }

    private function makeSchoolAdmin(School $school): User
    {
        $user = new User([
            'first_name' => 'Admin',
            'last_name' => $school->slug,
            'email' => 'admin-'.$school->slug.'@example.test',
            'password' => bcrypt('password'),
        ]);
        $user->role = 'school_admin';
        $user->school_id = $school->id;
        $user->save();

        return $user;
    }

    /**
     * Construit une inscription complète (année, classe, élève, inscription, échéance)
     * pour une école donnée, prête à recevoir un paiement.
     */
    private function makeEnrollmentContext(School $school): array
    {
        $year = SchoolYear::create([
            'school_id' => $school->id,
            'name' => '2026-2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-01',
            'is_active' => true,
        ]);

        $class = SchoolClass::create([
            'school_id' => $school->id,
            'name' => 'CP',
            'level' => 'primaire',
        ]);

        // school_id n'est volontairement pas mass-assignable sur Student (voir
        // App\Models\Traits\BelongsToSchool) : on l'affecte directement.
        $student = new Student([
            'first_name' => 'Eleve',
            'last_name' => $school->slug,
            'birth_date' => '2018-01-01',
            'gender' => 'M',
        ]);
        $student->school_id = $school->id;
        $student->save();

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'school_id' => $school->id,
            'school_year_id' => $year->id,
            'school_class_id' => $class->id,
            'status' => 'enrolled',
            'enrollment_date' => '2026-09-01',
            'tuition_fee_total' => 100000,
        ]);

        $installment = StudentInstallment::create([
            'school_id' => $school->id,
            'enrollment_id' => $enrollment->id,
            'type' => 'installment',
            'description' => 'Tranche 1',
            'amount' => 50000,
            'due_date' => '2026-10-01',
        ]);

        return compact('year', 'class', 'student', 'enrollment', 'installment');
    }

    public function test_payment_store_rejects_ids_belonging_to_another_school(): void
    {
        Storage::fake('public');

        $schoolA = $this->makeSchool('ecole-a');
        $schoolB = $this->makeSchool('ecole-b');
        $accountantA = $this->makeAccountant($schoolA);
        $victim = $this->makeEnrollmentContext($schoolB);

        $response = $this->actingAs($accountantA)->post(route('app.payments.store'), [
            'student_id' => $victim['student']->id,
            'enrollment_id' => $victim['enrollment']->id,
            'student_installment_id' => $victim['installment']->id,
            'amount' => 10000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'payment_type' => 'tuition',
        ]);

        $response->assertNotFound();

        $this->assertDatabaseMissing('payments', ['school_id' => $schoolB->id]);
        $victim['installment']->refresh();
        $this->assertSame(0.0, (float) $victim['installment']->paid_amount);
    }

    public function test_payment_store_succeeds_for_own_school_ids(): void
    {
        Storage::fake('public');

        $schoolA = $this->makeSchool('ecole-a');
        $accountantA = $this->makeAccountant($schoolA);
        $ctx = $this->makeEnrollmentContext($schoolA);

        $response = $this->actingAs($accountantA)->post(route('app.payments.store'), [
            'student_id' => $ctx['student']->id,
            'enrollment_id' => $ctx['enrollment']->id,
            'student_installment_id' => $ctx['installment']->id,
            'amount' => 10000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'payment_type' => 'tuition',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('payments', [
            'school_id' => $schoolA->id,
            'enrollment_id' => $ctx['enrollment']->id,
            'amount' => 10000,
        ]);
    }

    public function test_financial_student_detail_rejects_student_of_another_school(): void
    {
        $schoolA = $this->makeSchool('ecole-a');
        $schoolB = $this->makeSchool('ecole-b');
        $schoolAdminA = $this->makeSchoolAdmin($schoolA);
        $victim = $this->makeEnrollmentContext($schoolB);

        $this->actingAs($schoolAdminA)
            ->get(route('app.financial.student_detail', $victim['student']->id))
            ->assertNotFound();
    }

    public function test_financial_export_student_detail_pdf_rejects_student_of_another_school(): void
    {
        $schoolA = $this->makeSchool('ecole-a');
        $schoolB = $this->makeSchool('ecole-b');
        $schoolAdminA = $this->makeSchoolAdmin($schoolA);
        $victim = $this->makeEnrollmentContext($schoolB);

        $this->actingAs($schoolAdminA)
            ->get(route('app.financial.export.student_detail.pdf', $victim['student']->id))
            ->assertNotFound();
    }

    public function test_financial_export_student_detail_excel_rejects_student_of_another_school(): void
    {
        $schoolA = $this->makeSchool('ecole-a');
        $schoolB = $this->makeSchool('ecole-b');
        $schoolAdminA = $this->makeSchoolAdmin($schoolA);
        $victim = $this->makeEnrollmentContext($schoolB);

        $this->actingAs($schoolAdminA)
            ->get(route('app.financial.export.student_detail.excel', $victim['student']->id))
            ->assertNotFound();
    }

    public function test_financial_export_class_detail_pdf_rejects_class_of_another_school(): void
    {
        $schoolA = $this->makeSchool('ecole-a');
        $schoolB = $this->makeSchool('ecole-b');
        $schoolAdminA = $this->makeSchoolAdmin($schoolA);
        $victim = $this->makeEnrollmentContext($schoolB);

        $this->actingAs($schoolAdminA)
            ->get(route('app.financial.export.class_detail.pdf', $victim['class']->id))
            ->assertNotFound();
    }

    public function test_financial_export_class_detail_excel_rejects_class_of_another_school(): void
    {
        $schoolA = $this->makeSchool('ecole-a');
        $schoolB = $this->makeSchool('ecole-b');
        $schoolAdminA = $this->makeSchoolAdmin($schoolA);
        $victim = $this->makeEnrollmentContext($schoolB);

        $this->actingAs($schoolAdminA)
            ->get(route('app.financial.export.class_detail.excel', $victim['class']->id))
            ->assertNotFound();
    }

    public function test_report_card_create_rejects_class_of_another_school(): void
    {
        $schoolA = $this->makeSchool('ecole-a');
        $schoolB = $this->makeSchool('ecole-b');
        $schoolAdminA = $this->makeSchoolAdmin($schoolA);
        $victim = $this->makeEnrollmentContext($schoolB);

        $this->actingAs($schoolAdminA)
            ->get(route('app.report-cards.create', ['class_id' => $victim['class']->id]))
            ->assertForbidden();
    }

    public function test_end_of_year_rejects_class_and_student_of_another_school(): void
    {
        $schoolA = $this->makeSchool('ecole-a');
        $schoolB = $this->makeSchool('ecole-b');
        $schoolAdminA = $this->makeSchoolAdmin($schoolA);
        $own = $this->makeEnrollmentContext($schoolA);
        $victim = $this->makeEnrollmentContext($schoolB);

        $this->actingAs($schoolAdminA)->get(route('app.end-of-year.show', $victim['class']))->assertForbidden();
        $this->actingAs($schoolAdminA)->post(route('app.end-of-year.migrate', $victim['class']))->assertForbidden();
        // Student porte un scope global par école : l'élève d'une autre école est introuvable.
        $this->actingAs($schoolAdminA)
            ->post(route('app.end-of-year.update-decision', $victim['student']), ['decision' => 'admis'])
            ->assertNotFound();

        $this->actingAs($schoolAdminA)
            ->post(route('app.end-of-year.update-decision', $own['student']), ['decision' => 'saut_classe', 'next_school_class_id' => $victim['class']->id])
            ->assertSessionHasErrors('next_school_class_id');
    }

    public function test_end_of_year_uses_active_year_of_own_school(): void
    {
        $schoolA = $this->makeSchool('ecole-a');
        $schoolB = $this->makeSchool('ecole-b');
        // L'école B est créée en premier : avant le correctif, son année active était prise pour celle de A.
        $this->makeEnrollmentContext($schoolB);
        $own = $this->makeEnrollmentContext($schoolA);
        $schoolAdminA = $this->makeSchoolAdmin($schoolA);

        $this->actingAs($schoolAdminA)
            ->get(route('app.end-of-year.show', $own['class']))
            ->assertOk()
            ->assertViewHas('currentYear', fn ($year) => $year->id === $own['year']->id);
    }

    public function test_broadcast_cannot_target_parent_or_class_of_another_school(): void
    {
        $schoolA = $this->makeSchool('ecole-a');
        $schoolB = $this->makeSchool('ecole-b');
        $schoolAdminA = $this->makeSchoolAdmin($schoolA);
        $victim = $this->makeEnrollmentContext($schoolB);

        $parentB = new User(['first_name' => 'Parent', 'last_name' => 'B', 'email' => 'parent-b@example.test', 'password' => bcrypt('password')]);
        $parentB->role = 'parent';
        $parentB->school_id = $schoolB->id;
        $parentB->save();
        $parentB->children()->attach($victim['student']->id, ['school_id' => $schoolB->id]);

        $this->actingAs($schoolAdminA)
            ->post(route('app.messages.broadcast.store'), ['target_type' => 'parent', 'receiver_id' => $parentB->id, 'subject' => 'S', 'message' => 'M'])
            ->assertSessionHasErrors('receiver_id');
        $this->actingAs($schoolAdminA)
            ->post(route('app.messages.broadcast.store'), ['target_type' => 'class', 'target_id' => $victim['class']->id, 'subject' => 'S', 'message' => 'M'])
            ->assertSessionHasErrors('target_id');

        $this->assertDatabaseCount('messages', 0);
    }
}
