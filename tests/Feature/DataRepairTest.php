<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\StudentInstallment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Total de scolarité des inscriptions, niveaux « CP » ambigus, classes sans cycle.
 */
class DataRepairTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private SchoolYear $year;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['name' => 'La pépite', 'slug' => 'la-pepite', 'status' => 'active', 'school_type' => 'both']);
        $this->year = SchoolYear::create(['school_id' => $this->school->id, 'name' => '2026-2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-01', 'is_active' => true]);
    }

    private function enrollment(SchoolClass $class): Enrollment
    {
        $student = new Student(['first_name' => 'Mohammed', 'last_name' => 'Kone', 'birth_date' => '2019-01-01', 'gender' => 'M']);
        $student->school_id = $this->school->id;
        $student->save();

        return Enrollment::create([
            'student_id' => $student->id, 'school_id' => $this->school->id, 'school_year_id' => $this->year->id,
            'school_class_id' => $class->id, 'status' => 'enrolled', 'enrollment_date' => '2026-09-05',
        ]);
    }

    private function cp1Class(array $attributes = []): SchoolClass
    {
        return SchoolClass::create(array_merge([
            'school_id' => $this->school->id, 'name' => 'CP1', 'level' => 'CP1', 'cycle' => 'primaire',
            'total_tuition' => 300000, 'registration_fee' => 50000, 'payment_modality' => 'mensuel',
            'number_of_installments' => 4, 'installment_amount' => 62500,
        ], $attributes));
    }

    public function test_fee_schedule_sets_the_enrollment_tuition_total(): void
    {
        $enrollment = $this->enrollment($class = $this->cp1Class());

        StudentInstallment::generateScheduleFor($enrollment, $class, '2026-09-05');

        $enrollment->refresh();
        $this->assertEquals(250000, $enrollment->tuition_fee_total);   // 4 × 62 500, hors inscription
        $this->assertEquals(250000, $enrollment->tuition_fee_remaining);
    }

    public function test_repair_migration_fixes_levels_cycles_and_enrollment_totals(): void
    {
        $cp1 = $this->cp1Class(['name' => 'CP1 A', 'level' => 'CP']);
        $cp2 = $this->cp1Class(['name' => 'CP2', 'level' => 'CP']);
        $plainCp = $this->cp1Class(['name' => 'Cours préparatoire', 'level' => 'CP']);
        $noCycle = $this->cp1Class(['name' => 'Petite Section', 'level' => 'PS', 'cycle' => null]);

        // Inscription « cassée » comme en production : total 0, 10 000 payés → reste -10 000
        $enrollment = $this->enrollment($cp1);
        DB::table('student_installments')->insert([
            ['school_id' => $this->school->id, 'enrollment_id' => $enrollment->id, 'type' => 'registration', 'description' => "Frais d'inscription", 'amount' => 50000, 'paid_amount' => 50000, 'due_date' => '2026-09-05', 'status' => 'paid'],
            ['school_id' => $this->school->id, 'enrollment_id' => $enrollment->id, 'type' => 'installment', 'description' => '1ère échéance', 'amount' => 62500, 'paid_amount' => 10000, 'due_date' => '2026-10-05', 'status' => 'partial'],
            ['school_id' => $this->school->id, 'enrollment_id' => $enrollment->id, 'type' => 'installment', 'description' => '2ème échéance', 'amount' => 62500, 'paid_amount' => 0, 'due_date' => '2026-11-05', 'status' => 'pending'],
        ]);
        $enrollment->update(['tuition_fee_total' => 0, 'tuition_fee_paid' => 10000, 'tuition_fee_remaining' => -10000]);

        // Anciens forfaits « premium » / « basic »
        SubscriptionPlan::create(['name' => 'Essentiel', 'slug' => 'essentiel', 'max_students' => 100, 'max_users' => 3, 'monthly_price' => 10000]);
        $legacyBasic = School::create(['name' => 'Démo', 'slug' => 'demo', 'status' => 'active', 'school_type' => 'both', 'subscription_plan' => 'basic', 'max_users' => null]);
        $legacyPremium = School::create(['name' => 'Démo 2', 'slug' => 'demo-2', 'status' => 'active', 'school_type' => 'both', 'subscription_plan' => 'premium']);

        (require database_path('migrations/2026_11_26_120000_repair_class_levels_and_enrollment_totals.php'))->up();

        $this->assertSame('Essentiel', $legacyBasic->fresh()->subscription_plan);
        $this->assertSame(3, (int) $legacyBasic->fresh()->max_users);
        $this->assertSame('Premium', $legacyPremium->fresh()->subscription_plan);

        $this->assertSame('CP1', $cp1->fresh()->level);
        $this->assertSame('CP2', $cp2->fresh()->level);
        $this->assertSame('CP', $plainCp->fresh()->level);           // nom sans CP1/CP2 : inchangé
        $this->assertSame('maternelle', $noCycle->fresh()->cycle);

        $enrollment->refresh();
        $this->assertEquals(125000, $enrollment->tuition_fee_total);
        $this->assertEquals(10000, $enrollment->tuition_fee_paid);
        $this->assertEquals(115000, $enrollment->tuition_fee_remaining);
    }

    public function test_ambiguous_cp_level_is_no_longer_offered_for_new_classes(): void
    {
        $admin = new User(['first_name' => 'D', 'last_name' => 'A', 'email' => 'dir@example.test', 'password' => bcrypt('password')]);
        $admin->role = 'school_admin';
        $admin->school_id = $this->school->id;
        $admin->save();

        $html = $this->actingAs($admin)->get(route('app.classes.create'))->assertOk()->getContent();

        $this->assertStringContainsString('"CP1"', $html);
        $this->assertStringNotContainsString('"CP",', $html);
    }
}
