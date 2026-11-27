<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CareStaffAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use App\Rules\GroupHasRoom;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Module Crèche — lot 0 : structures multiples, groupes de crèche, rôles et droits crèche,
 * affectations du personnel, paramètres, contrôle de capacité et journal d'audit.
 */
class CrecheFoundationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['name' => 'Les Mirabelles', 'slug' => 'les-mirabelles', 'status' => 'active', 'structures' => ['creche', 'maternelle']]);
        SchoolYear::create(['school_id' => $this->school->id, 'name' => '2026-2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-01', 'is_active' => true]);
        $this->admin = $this->makeUser('school_admin', 'directeur@example.test');
    }

    private function makeUser(string $role, string $email, ?School $school = null): User
    {
        $user = new User(['first_name' => 'Awa', 'last_name' => $role, 'email' => $email, 'password' => bcrypt('password')]);
        $user->role = $role;
        $user->school_id = ($school ?? $this->school)->id;
        $user->save();

        return $user;
    }

    private function makeGroup(string $name, array $attributes = []): SchoolClass
    {
        return SchoolClass::create(array_merge([
            'school_id' => $this->school->id, 'name' => $name, 'level' => 'Bébés (0-1 an)', 'cycle' => 'creche', 'capacity' => 10,
        ], $attributes));
    }

    private function makeChild(SchoolClass $group, string $firstName = 'Adam'): Student
    {
        $student = new Student(['first_name' => $firstName, 'last_name' => 'Koné', 'birth_date' => '2025-06-01', 'gender' => 'M']);
        $student->school_id = $this->school->id;
        $student->save();
        $student->classes()->attach($group->id);

        return $student;
    }

    // ------------------------------------------------------------------
    // Structures
    // ------------------------------------------------------------------

    public function test_structures_and_legacy_school_type_stay_in_sync(): void
    {
        $this->assertSame(['creche', 'maternelle'], $this->school->structureList());
        $this->assertSame('multi', $this->school->school_type);
        $this->assertTrue($this->school->isCreche());
        $this->assertTrue($this->school->isMaternelle());
        $this->assertFalse($this->school->isPrimaire());
        $this->assertFalse($this->school->isCrecheOnly());
        $this->assertSame('Crèche + Maternelle', $this->school->school_type_label);

        // Ancien chemin (inscription publique d'une école) : school_type seul
        $legacy = School::create(['name' => 'Ancienne', 'slug' => 'ancienne', 'status' => 'active', 'school_type' => 'both']);
        $this->assertSame(['maternelle', 'primaire'], $legacy->fresh()->structureList());

        $crecheOnly = School::create(['name' => 'Crèche', 'slug' => 'creche', 'status' => 'active', 'school_type' => 'creche']);
        $this->assertTrue($crecheOnly->fresh()->isCrecheOnly());
    }

    public function test_director_activates_structures_from_school_profile(): void
    {
        $this->actingAs($this->admin)->put(route('app.profile.update'), [
            'first_name' => 'Awa', 'last_name' => 'Directrice', 'user_email' => 'directeur@example.test',
            'user_phone' => null, 'school_address' => null, 'school_email' => null, 'school_phone' => null,
            'school_name' => 'Les Mirabelles', 'structures' => ['primaire', 'creche', 'maternelle'],
        ])->assertSessionHasNoErrors();

        $school = $this->school->fresh();
        $this->assertSame(['creche', 'maternelle', 'primaire'], $school->structureList());

        $this->actingAs($this->admin)->put(route('app.profile.update'), [
            'first_name' => 'Awa', 'last_name' => 'Directrice', 'user_email' => 'directeur@example.test',
            'user_phone' => null, 'school_address' => null, 'school_email' => null, 'school_phone' => null,
            'school_name' => 'Les Mirabelles', 'structures' => [],
        ])->assertSessionHasErrors('structures');
    }

    // ------------------------------------------------------------------
    // Groupes de crèche
    // ------------------------------------------------------------------

    public function test_director_creates_a_creche_group_with_its_own_details(): void
    {
        $auxiliary = $this->makeUser('care_staff', 'aux@example.test');

        $this->actingAs($this->admin)->get(route('app.classes.create'))
            ->assertOk()->assertSee('Groupe de crèche')->assertSee('Bébés (0-1 an)');

        $this->actingAs($this->admin)->post(route('app.classes.store'), [
            'name' => 'Les Poussins', 'code' => 'BB1', 'cycle' => 'creche', 'level' => 'Bébés (0-1 an)', 'capacity' => 8,
            'age_min_months' => 3, 'age_max_months' => 12, 'room' => 'Salle bleue', 'responsible_user_id' => $auxiliary->id,
            'opening_time' => '07:30', 'closing_time' => '17:30',
        ])->assertRedirect(route('app.classes.index'))->assertSessionHasNoErrors();

        $group = SchoolClass::where('name', 'Les Poussins')->firstOrFail();
        $this->assertSame('creche', $group->cycle);
        $this->assertSame('BB1', $group->code);
        $this->assertSame(8, $group->capacity);
        $this->assertSame(12, $group->age_max_months);
        $this->assertSame($auxiliary->id, $group->responsible_user_id);
        $this->assertSame('17:30', $group->effectiveClosingTime());
    }

    public function test_creche_details_are_dropped_for_school_classes_and_bad_ranges_refused(): void
    {
        $this->actingAs($this->admin)->post(route('app.classes.store'), [
            'name' => 'Grande section', 'cycle' => 'maternelle', 'level' => 'GS', 'room' => 'Salle 3', 'opening_time' => '08:00',
        ])->assertSessionHasNoErrors();
        $class = SchoolClass::where('name', 'Grande section')->firstOrFail();
        $this->assertNull($class->room);
        $this->assertNull($class->opening_time);

        $this->actingAs($this->admin)->post(route('app.classes.store'), [
            'name' => 'Les Lutins', 'cycle' => 'creche', 'level' => 'Bébés (0-1 an)', 'age_min_months' => 12, 'age_max_months' => 6,
        ])->assertSessionHasErrors('age_max_months');

        $this->actingAs($this->admin)->post(route('app.classes.store'), [
            'name' => 'Les Lutins', 'cycle' => 'creche', 'level' => 'Bébés (0-1 an)', 'opening_time' => '18:00', 'closing_time' => '07:00',
        ])->assertSessionHasErrors('closing_time');
    }

    public function test_a_cycle_that_is_not_activated_is_refused(): void
    {
        $this->actingAs($this->admin)->post(route('app.classes.store'), [
            'name' => 'CM2 A', 'cycle' => 'primaire', 'level' => 'CM2',
        ])->assertSessionHasErrors('cycle');

        $school = School::create(['name' => 'Primaire seule', 'slug' => 'primaire-seule', 'status' => 'active', 'school_type' => 'primaire']);
        $director = $this->makeUser('school_admin', 'dir2@example.test', $school);
        $this->actingAs($director)->post(route('app.classes.store'), [
            'name' => 'Les Poussins', 'cycle' => 'creche', 'level' => 'Bébés (0-1 an)',
        ])->assertSessionHasErrors('cycle');
    }

    public function test_age_groups_come_from_creche_settings(): void
    {
        $this->actingAs($this->admin)->put(route('app.creche.settings.update'), [
            'levels' => "Nourrissons\nTrotteurs\n\nTrotteurs\nGrands",
            'opening_time' => '06:30', 'closing_time' => '18:30', 'enforce_capacity' => '1',
        ])->assertRedirect(route('app.creche.settings.edit'))->assertSessionHasNoErrors();

        $school = $this->school->fresh();
        $this->assertSame(['Nourrissons', 'Trotteurs', 'Grands'], $school->crecheLevels());
        $this->assertSame('18:30', $school->setting('creche.closing_time'));
        $this->assertTrue((bool) $school->setting('creche.enforce_capacity'));

        // Le nouveau niveau est accepté, l'ancien ne l'est plus pour un nouveau groupe...
        $this->actingAs($this->admin)->post(route('app.classes.store'), ['name' => 'Les Lutins', 'cycle' => 'creche', 'level' => 'Trotteurs'])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post(route('app.classes.store'), ['name' => 'Les Fées', 'cycle' => 'creche', 'level' => 'Bébés (0-1 an)'])
            ->assertSessionHasErrors('level');

        // ... mais un groupe qui le porte déjà peut toujours être modifié.
        $old = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Ancien', 'level' => 'Bébés (0-1 an)', 'cycle' => 'creche']);
        $this->actingAs($this->admin)->get(route('app.classes.edit', $old))->assertOk()->assertSee('Bébés (0-1 an)');
        $this->actingAs($this->admin)->put(route('app.classes.update', $old), ['name' => 'Ancien renommé', 'cycle' => 'creche', 'level' => 'Bébés (0-1 an)'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Ancien renommé', $old->fresh()->name);
    }

    public function test_full_group_is_refused_only_when_capacity_control_is_on(): void
    {
        $group = $this->makeGroup('Les Poussins', ['capacity' => 1]);
        $child = $this->makeChild($group);
        $rule = fn (?int $except = null) => Validator::make(['class_id' => $group->id], ['class_id' => [new GroupHasRoom($except)]]);

        $this->assertTrue($rule()->passes(), 'Sans contrôle activé, la capacité reste indicative.');

        $this->school->update(['settings' => ['creche' => ['enforce_capacity' => true]]]);

        $this->assertTrue($rule()->fails());
        $this->assertTrue($rule($child->id)->passes(), "L'enfant déjà dans le groupe peut modifier sa fiche.");

        $maternelle = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'PS A', 'level' => 'PS', 'cycle' => 'maternelle', 'capacity' => 1]);
        $this->makeChild($maternelle, 'Sarah');
        $this->assertTrue(Validator::make(['class_id' => $maternelle->id], ['class_id' => [new GroupHasRoom]])->passes(), 'Les classes de maternelle ne sont pas concernées.');
    }

    // ------------------------------------------------------------------
    // Rôles, droits et affectations
    // ------------------------------------------------------------------

    public function test_creche_roles_are_offered_only_to_schools_with_a_creche(): void
    {
        $this->actingAs($this->admin)->get(route('app.staff.create'))
            ->assertOk()->assertSee('Auxiliaire de crèche')->assertSee('Pointer les arrivées et départs des enfants');

        $this->actingAs($this->admin)->post(route('app.staff.store'), [
            'first_name' => 'Awa', 'last_name' => 'Traoré', 'email' => 'awa@example.test',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
            'gender' => 'F', 'role' => 'care_staff', 'permissions_mode' => 'default',
        ])->assertSessionHasNoErrors();
        $auxiliary = User::where('email', 'awa@example.test')->firstOrFail();
        $this->assertSame(Permissions::ROLE_DEFAULTS['care_staff'], $auxiliary->effectivePermissions());
        $this->assertSame('app.creche-attendances.index', $auxiliary->dashboardRouteName());

        $other = School::create(['name' => 'Sans crèche', 'slug' => 'sans-creche', 'status' => 'active', 'school_type' => 'both']);
        $director = $this->makeUser('school_admin', 'dir2@example.test', $other);
        $this->actingAs($director)->get(route('app.staff.create'))
            ->assertOk()->assertDontSee('Auxiliaire de crèche')->assertDontSee('Pointer les arrivées et départs des enfants');
        $this->actingAs($director)->post(route('app.staff.store'), [
            'first_name' => 'Ali', 'last_name' => 'Bamba', 'email' => 'ali@example.test',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
            'gender' => 'M', 'role' => 'care_staff', 'permissions_mode' => 'default',
        ])->assertSessionHasErrors('role');
    }

    public function test_care_manager_assigns_staff_and_auxiliary_sees_only_her_groups(): void
    {
        $babies = $this->makeGroup('Bébés');
        $toddlers = $this->makeGroup('Petits', ['level' => 'Moyens (1-2 ans)']);
        $adam = $this->makeChild($babies, 'Adam');
        $sarah = $this->makeChild($toddlers, 'Sarah');

        $manager = $this->makeUser('care_manager', 'resp@example.test');
        $auxiliary = $this->makeUser('care_staff', 'aux@example.test');

        // L'auxiliaire n'a pas accès aux affectations ni aux paramètres
        $this->actingAs($auxiliary)->get(route('app.creche.assignments.index'))->assertForbidden();
        $this->actingAs($auxiliary)->get(route('app.creche.settings.edit'))->assertForbidden();

        // Avant affectation : aucun groupe visible
        $this->actingAs($auxiliary)->get(route('app.creche-attendances.index'))
            ->assertOk()->assertDontSee('Bébés (Bébés (0-1 an))');

        $this->actingAs($manager)->post(route('app.creche.assignments.store'), [
            'school_class_id' => $babies->id, 'user_id' => $auxiliary->id, 'is_referent' => '1',
        ])->assertRedirect(route('app.creche.assignments.index'))->assertSessionHasNoErrors();
        $this->assertTrue(CareStaffAssignment::where('user_id', $auxiliary->id)->where('school_class_id', $babies->id)->value('is_referent'));

        $this->assertSame([$babies->id], $auxiliary->fresh()->accessibleCrecheClassIds());
        $this->assertEqualsCanonicalizing([$babies->id, $toddlers->id], $manager->accessibleCrecheClassIds());
        $this->assertEqualsCanonicalizing([$babies->id, $toddlers->id], $this->admin->accessibleCrecheClassIds());

        $this->actingAs($auxiliary)->get(route('app.creche-attendances.index', ['class_id' => $babies->id]))
            ->assertOk()->assertSee('Adam')->assertDontSee('Sarah');
        $this->actingAs($auxiliary)->get(route('app.creche-attendances.index', ['class_id' => $toddlers->id]))
            ->assertForbidden();

        // Pointage d'un groupe non affecté : refusé
        $this->actingAs($auxiliary)->post(route('app.creche-attendances.store'), [
            'class_id' => $toddlers->id, 'date' => '2026-11-27', 'records' => [$sarah->id => ['status' => 'present']],
        ])->assertForbidden();

        // Pointage de son groupe : un enfant d'un autre groupe glissé dans la requête est ignoré
        $this->actingAs($auxiliary)->post(route('app.creche-attendances.store'), [
            'class_id' => $babies->id, 'date' => '2026-11-27',
            'records' => [$adam->id => ['status' => 'present', 'checked_in_at' => '07:42'], $sarah->id => ['status' => 'present']],
        ])->assertRedirect();
        $this->assertDatabaseHas('creche_attendances', ['student_id' => $adam->id, 'status' => 'present']);
        $this->assertDatabaseMissing('creche_attendances', ['student_id' => $sarah->id]);

        // Journal d'audit rattaché à l'école et au groupe
        $log = ActivityLog::where('action', 'creche.attendance.saved')->firstOrFail();
        $this->assertSame($this->school->id, $log->school_id);
        $this->assertSame($auxiliary->id, $log->user_id);
        $this->assertSame('SchoolClass', $log->subject_type);
        $this->assertSame($babies->id, $log->subject_id);
        $this->assertSame(1, $log->meta['children']);
    }

    public function test_assignments_refuse_people_without_creche_rights_and_other_schools_groups(): void
    {
        $group = $this->makeGroup('Bébés');
        $accountant = $this->makeUser('accountant', 'compta@example.test');

        $this->actingAs($this->admin)->post(route('app.creche.assignments.store'), [
            'school_class_id' => $group->id, 'user_id' => $accountant->id,
        ])->assertSessionHasErrors('user_id');

        $other = School::create(['name' => 'Autre', 'slug' => 'autre', 'status' => 'active', 'structures' => ['creche']]);
        $foreignGroup = SchoolClass::create(['school_id' => $other->id, 'name' => 'Ailleurs', 'level' => 'Bébés (0-1 an)', 'cycle' => 'creche']);
        $auxiliary = $this->makeUser('care_staff', 'aux@example.test');

        $this->actingAs($this->admin)->post(route('app.creche.assignments.store'), [
            'school_class_id' => $foreignGroup->id, 'user_id' => $auxiliary->id,
        ])->assertSessionHasErrors('school_class_id');

        // Une affectation d'une autre école ne peut pas être supprimée d'ici
        $foreignAux = $this->makeUser('care_staff', 'aux2@example.test', $other);
        $foreign = CareStaffAssignment::create(['school_id' => $other->id, 'school_class_id' => $foreignGroup->id, 'user_id' => $foreignAux->id]);
        $this->actingAs($this->admin)->delete(route('app.creche.assignments.destroy', $foreign))->assertNotFound();
        $this->assertDatabaseHas('care_staff_assignments', ['id' => $foreign->id]);
    }

    public function test_creche_pages_are_hidden_for_schools_without_a_creche(): void
    {
        $other = School::create(['name' => 'Sans crèche', 'slug' => 'sans-creche', 'status' => 'active', 'school_type' => 'both']);
        $director = $this->makeUser('school_admin', 'dir2@example.test', $other);

        $this->actingAs($director)->get(route('app.creche.settings.edit'))->assertNotFound();
        $this->actingAs($director)->get(route('app.creche.assignments.index'))->assertNotFound();

        $this->actingAs($this->admin)->get(route('app.creche.settings.edit'))->assertOk()->assertSee('Groupes d');
        $this->actingAs($this->admin)->get(route('app.creche.assignments.index'))->assertOk();
    }
}
