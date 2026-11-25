<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\StudentInstallment;
use App\Models\Subject;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verrouille les corrections issues du document "Preuves de tests" (recette 09/2026).
 */
class TestFeedbackFixesTest extends TestCase
{
    use RefreshDatabase;

    private function makeSchool(array $attributes = []): School
    {
        return School::create(array_merge([
            'name' => 'La pépite de demain',
            'slug' => 'pepite-'.uniqid(),
            'status' => 'active',
            'school_type' => 'both',
        ], $attributes));
    }

    private function makeUser(?School $school, string $role, string $email): User
    {
        $user = new User([
            'first_name' => 'Test',
            'last_name' => $role,
            'email' => $email,
            'password' => bcrypt('password'),
        ]);
        $user->role = $role;
        $user->school_id = $school?->id;
        $user->save();

        return $user;
    }

    private function makeActiveYear(School $school): SchoolYear
    {
        return SchoolYear::create([
            'school_id' => $school->id,
            'name' => '2026-2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-01',
            'is_active' => true,
        ]);
    }

    // ---------------------------------------------------------------
    // 1. Super Admin : statut réel (essai) + demande en attente, en français
    // ---------------------------------------------------------------

    public function test_superadmin_school_list_shows_trial_status_and_pending_request_in_french(): void
    {
        $school = $this->makeSchool(['trial_ends_at' => now()->addDays(13)]);
        $plan = SubscriptionPlan::create(['name' => 'Essentiel', 'slug' => 'essentiel']);
        SubscriptionRequest::create(['school_id' => $school->id, 'plan_id' => $plan->id, 'duration' => 'yearly', 'status' => 'pending']);
        $superAdmin = $this->makeUser(null, 'super_admin', 'super@example.test');

        $response = $this->actingAs($superAdmin)->get(route('superadmin.schools.index'));

        $response->assertOk()
            ->assertSee('Essai')
            ->assertSee('Maternelle + Primaire')
            ->assertSee('Abonnement en attente');
        $this->assertDoesNotMatchRegularExpression('/>\s*(active|both)\s*</', $response->getContent());
    }

    public function test_superadmin_status_filters_separate_trial_and_active_schools(): void
    {
        $this->makeSchool(['name' => 'Ecole En Essai', 'trial_ends_at' => now()->addDays(5)]);
        $this->makeSchool(['name' => 'Ecole Payante', 'subscription_end_date' => now()->addYear()]);
        $superAdmin = $this->makeUser(null, 'super_admin', 'super@example.test');

        $this->actingAs($superAdmin)->get(route('superadmin.schools.index', ['status' => 'trial']))
            ->assertSee('Ecole En Essai')->assertDontSee('Ecole Payante');

        $this->actingAs($superAdmin)->get(route('superadmin.schools.index', ['status' => 'active']))
            ->assertSee('Ecole Payante')->assertDontSee('Ecole En Essai');
    }

    // ---------------------------------------------------------------
    // 2. Classe créée sans capacité : plus d'erreur SQL
    // ---------------------------------------------------------------

    public function test_class_can_be_created_without_capacity(): void
    {
        $school = $this->makeSchool();
        $this->makeActiveYear($school);
        $admin = $this->makeUser($school, 'school_admin', 'admin@example.test');

        $this->actingAs($admin)->post(route('app.classes.store'), [
            'name' => 'TPS1',
            'level' => 'TPS',
            'cycle' => 'maternelle',
            'capacity' => '',
        ])->assertRedirect(route('app.classes.index'));

        $this->assertDatabaseHas('school_classes', [
            'school_id' => $school->id,
            'name' => 'TPS1',
            'capacity' => SchoolClass::DEFAULT_CAPACITY,
        ]);
    }

    // ---------------------------------------------------------------
    // 3. Matières : lignes vides / incomplètes n'empêchent plus l'enregistrement
    // ---------------------------------------------------------------

    public function test_subjects_are_saved_even_with_empty_or_partial_rows(): void
    {
        $school = $this->makeSchool();
        $year = $this->makeActiveYear($school);
        $admin = $this->makeUser($school, 'school_admin', 'admin@example.test');

        $this->actingAs($admin)->post(route('app.subjects.store'), [
            'cycle' => 'primaire',
            'level' => 'CP1',
            'subjects' => [
                0 => ['name' => 'Lecture', 'coefficient' => 2, 'max_score' => 20],
                5 => ['name' => 'Dictée', 'coefficient' => 1],   // barème perdu : défaut 20
                14 => ['max_score' => 20],                        // ligne orpheline : ignorée
                15 => ['name' => '', 'coefficient' => 1, 'max_score' => 20], // ligne vide : ignorée
            ],
        ])->assertRedirect(route('app.subjects.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Subject::where('school_year_id', $year->id)->where('level', 'CP1')->count());
        $this->assertDatabaseHas('subjects', ['name' => 'Dictée', 'max_score' => 20]);
    }

    public function test_subject_validation_messages_are_in_french(): void
    {
        $school = $this->makeSchool();
        $this->makeActiveYear($school);
        $admin = $this->makeUser($school, 'school_admin', 'admin@example.test');

        $this->actingAs($admin)->post(route('app.subjects.store'), [
            'cycle' => 'primaire',
            'level' => 'CP1',
            'subjects' => [['name' => 'Lecture', 'coefficient' => 50, 'max_score' => 20]],
        ])->assertSessionHasErrors([
            'subjects.0.coefficient' => 'Le coefficient de la matière n° 1 ne peut pas dépasser 10.',
        ]);
    }

    // ---------------------------------------------------------------
    // 4 & 5. Bulletins : un seul menu actif + message "aucune matière" explicite
    // ---------------------------------------------------------------

    public function test_report_card_page_has_single_bulletins_menu_and_explains_level_mismatch(): void
    {
        $school = $this->makeSchool();
        $year = $this->makeActiveYear($school);
        $admin = $this->makeUser($school, 'school_admin', 'admin@example.test');
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'CP1', 'level' => 'CP', 'cycle' => 'primaire']);
        Subject::create([
            'school_id' => $school->id, 'school_year_id' => $year->id, 'cycle' => 'primaire',
            'level' => 'CP1', 'name' => 'Lecture', 'coefficient' => 1, 'max_score' => 20, 'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('app.report-cards.create', ['class_id' => $class->id]));

        $response->assertOk()
            ->assertSee('Aucune matière configurée pour ce niveau')
            ->assertSee("mais pour d'autres niveaux", false)
            ->assertSee('CP1')
            ->assertDontSee('Bulletins de notes');
    }

    // ---------------------------------------------------------------
    // 6. Espace parent : échéances présentées en tableau, dates en français
    // ---------------------------------------------------------------

    public function test_parent_payments_page_lists_installments_in_a_table(): void
    {
        $school = $this->makeSchool();
        $year = $this->makeActiveYear($school);
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'CP1', 'level' => 'CP1', 'cycle' => 'primaire']);

        $student = new Student(['first_name' => 'Mohammed', 'last_name' => 'Kone', 'birth_date' => '2018-01-01', 'gender' => 'M']);
        $student->school_id = $school->id;
        $student->save();

        $parent = $this->makeUser($school, 'parent', 'parent@example.test');
        $parent->children()->attach($student->id, ['school_id' => $school->id]);

        $enrollment = Enrollment::create([
            'student_id' => $student->id, 'school_id' => $school->id, 'school_year_id' => $year->id,
            'school_class_id' => $class->id, 'status' => 'enrolled', 'enrollment_date' => '2026-09-01',
            'tuition_fee_total' => 112500,
        ]);
        StudentInstallment::create([
            'school_id' => $school->id, 'enrollment_id' => $enrollment->id, 'type' => 'installment',
            'description' => '1ère échéance', 'amount' => 62500, 'paid_amount' => 10000, 'due_date' => '2026-10-05',
        ]);

        $response = $this->actingAs($parent)->get(route('parent.payments.index', $student->id));

        $response->assertOk()
            ->assertSee('<table', false)
            ->assertSee('1ère échéance')
            ->assertSee('Total (FCFA)')
            ->assertSee('52 500')
            ->assertSee('oct.')
            ->assertDontSee('October');
    }
}
