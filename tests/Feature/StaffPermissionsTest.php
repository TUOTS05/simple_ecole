<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolYear;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Écran unifié « Personnel » : rôle + droits ajustables par personne.
 */
class StaffPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['name' => 'Ecole Test', 'slug' => 'ecole-test', 'status' => 'active', 'school_type' => 'both']);
        SchoolYear::create(['school_id' => $this->school->id, 'name' => '2026-2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-01', 'is_active' => true]);
        $this->admin = $this->makeUser('school_admin', 'directeur@example.test');
    }

    private function makeUser(string $role, string $email, ?array $permissions = null, ?School $school = null): User
    {
        $user = new User(['first_name' => 'Test', 'last_name' => $role, 'email' => $email, 'password' => bcrypt('password')]);
        $user->role = $role;
        $user->school_id = ($school ?? $this->school)->id;
        $user->permissions = $permissions;
        $user->save();

        return $user;
    }

    private function staffPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Awa', 'last_name' => 'Koné', 'email' => 'awa@example.test',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
            'gender' => 'F', 'role' => 'secretary', 'permissions_mode' => 'default',
        ], $overrides);
    }

    public function test_director_creates_staff_with_role_defaults(): void
    {
        $this->actingAs($this->admin)->get(route('app.staff.create'))
            ->assertOk()->assertSee('Secrétaire')->assertSee('Personnaliser pour cette personne');

        $this->actingAs($this->admin)->post(route('app.staff.store'), $this->staffPayload())
            ->assertRedirect(route('app.staff.index'))->assertSessionHasNoErrors();

        $secretary = User::where('email', 'awa@example.test')->firstOrFail();
        $this->assertSame('secretary', $secretary->role);
        $this->assertSame($this->school->id, $secretary->school_id);
        $this->assertNull($secretary->permissions);
        $this->assertSame(Permissions::defaultsFor('secretary'), $secretary->effectivePermissions());

        $this->actingAs($this->admin)->get(route('app.staff.index'))
            ->assertOk()->assertSee('Koné')->assertSee('Secrétaire')->assertSee('Par défaut');
    }

    public function test_custom_permissions_replace_role_defaults_and_drive_access(): void
    {
        $this->actingAs($this->admin)->post(route('app.staff.store'), $this->staffPayload([
            'email' => 'compta@example.test', 'role' => 'accountant',
            'permissions_mode' => 'custom', 'permissions' => ['enrollments.manage', 'financial.view'],
        ]))->assertSessionHasNoErrors();

        $accountant = User::where('email', 'compta@example.test')->firstOrFail();
        $this->assertSame(['enrollments.manage', 'financial.view'], $accountant->permissions);

        $this->actingAs($accountant)->get(route('app.financial.unpaid_by_class'))->assertOk();
        $this->actingAs($accountant)->get(route('app.enrollments.index'))->assertOk();
        // Droit retiré par rapport au rôle comptable par défaut
        $this->actingAs($accountant)->get(route('app.payments.index'))->assertForbidden();
        $this->actingAs($accountant)->get(route('extras.payments.index'))->assertForbidden();
    }

    public function test_staff_management_right_can_never_be_granted(): void
    {
        $this->actingAs($this->admin)->post(route('app.staff.store'), $this->staffPayload([
            'permissions_mode' => 'custom', 'permissions' => ['students.manage', Permissions::STAFF_MANAGE],
        ]))->assertSessionHasErrors('permissions.1');

        $secretary = $this->makeUser('secretary', 'sec@example.test', ['students.manage', Permissions::STAFF_MANAGE]);
        $this->assertFalse($secretary->hasPermission(Permissions::STAFF_MANAGE));
        $this->actingAs($secretary)->get(route('app.staff.index'))->assertForbidden();
        $this->actingAs($secretary)->get(route('app.teachers.index'))->assertForbidden();
    }

    public function test_role_defaults_keep_historical_access(): void
    {
        $accountant = $this->makeUser('accountant', 'compta@example.test');
        $this->actingAs($accountant)->get(route('app.payments.index'))->assertOk();
        $this->actingAs($accountant)->get(route('extras.payments.index'))->assertOk();
        $this->actingAs($accountant)->get(route('app.students.index'))->assertForbidden();

        $canteen = $this->makeUser('canteen_manager', 'cantine@example.test');
        $this->actingAs($canteen)->get(route('extras.menus.index'))->assertOk();
        $this->actingAs($canteen)->get(route('extras.transport.vehicles.index'))->assertForbidden();

        $transport = $this->makeUser('transport_manager', 'bus@example.test');
        $this->actingAs($transport)->get(route('extras.transport.vehicles.index'))->assertOk();
        $this->actingAs($transport)->get(route('extras.menus.index'))->assertForbidden();
    }

    public function test_login_lands_on_first_allowed_screen(): void
    {
        $cases = [
            ['accountant', null, 'app.enrollments.index'],
            ['canteen_manager', null, 'extras.attendances.index'],
            ['transport_manager', null, 'extras.transport.vehicles.index'],
            ['secretary', null, 'app.enrollments.index'],
            ['secretary', ['messages.manage'], 'app.messages.index'],
        ];

        foreach ($cases as $i => [$role, $permissions, $route]) {
            $user = $this->makeUser($role, "user{$i}@example.test", $permissions);
            $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect(route($route));
            auth()->logout();
        }
    }

    public function test_teacher_and_parent_have_no_admin_screens_unless_granted(): void
    {
        $parent = $this->makeUser('parent', 'parent@example.test');
        $this->actingAs($parent)->get(route('app.students.index'))->assertForbidden();

        $teacher = $this->makeUser('teacher', 'prof@example.test');
        $this->actingAs($teacher)->get(route('app.report-cards.index'))->assertForbidden();

        $teacher->permissions = ['report_cards.manage'];
        $teacher->save();
        $this->actingAs($teacher->fresh())->get(route('app.report-cards.index'))->assertOk()->assertSee('Bulletins');
    }

    public function test_sidebar_only_shows_allowed_entries(): void
    {
        $accountant = $this->makeUser('accountant', 'compta@example.test');

        $this->actingAs($accountant)->get(route('app.enrollments.index'))
            ->assertOk()
            ->assertSee(route('app.payments.index'), false)
            ->assertDontSee(route('app.staff.index'), false)
            ->assertDontSee(route('app.students.index'), false);

        $this->actingAs($this->admin)->get(route('app.enrollments.index'))
            ->assertSee(route('app.staff.index'), false);
    }

    public function test_director_cannot_manage_other_schools_staff_or_non_staff_accounts(): void
    {
        $other = School::create(['name' => 'Autre', 'slug' => 'autre', 'status' => 'active', 'school_type' => 'both']);
        $foreign = $this->makeUser('secretary', 'foreign@example.test', null, $other);
        $parent = $this->makeUser('parent', 'parent@example.test');

        $this->actingAs($this->admin)->get(route('app.staff.edit', $foreign))->assertForbidden();
        $this->actingAs($this->admin)->get(route('app.staff.edit', $parent))->assertForbidden();
        $this->actingAs($this->admin)->delete(route('app.staff.destroy', $this->admin))->assertForbidden();
    }

    public function test_updating_staff_changes_role_and_resets_to_defaults(): void
    {
        $user = $this->makeUser('secretary', 'sec@example.test', ['messages.manage']);

        $this->actingAs($this->admin)->put(route('app.staff.update', $user), $this->staffPayload([
            'email' => 'sec@example.test', 'password' => '', 'password_confirmation' => '',
            'role' => 'accountant', 'permissions_mode' => 'default',
        ]))->assertRedirect(route('app.staff.index'))->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('accountant', $user->role);
        $this->assertNull($user->permissions);
        $this->assertTrue($user->hasPermission('payments.manage'));
    }

    public function test_user_limit_of_the_plan_is_enforced(): void
    {
        $this->school->update(['max_users' => 1]); // le directeur occupe déjà la place

        $this->actingAs($this->admin)->post(route('app.staff.store'), $this->staffPayload())
            ->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'awa@example.test']);
    }
}
