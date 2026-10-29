<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\StaffFunction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class StaffPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function makeSchool(string $slug): School
    {
        return School::create(['name' => 'École '.$slug, 'slug' => $slug, 'status' => 'active']);
    }

    private function makeFunction(School $school, array $permissions, string $name = 'Économe'): StaffFunction
    {
        $function = new StaffFunction(['name' => $name, 'permissions' => $permissions]);
        $function->school_id = $school->id;
        $function->save();

        return $function;
    }

    private function makeUser(School $school, string $role, ?StaffFunction $function = null): User
    {
        $user = new User([
            'first_name' => 'Test',
            'last_name' => $role,
            'email' => $role.'-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
        ]);
        $user->role = $role;
        $user->school_id = $school->id;
        $user->staff_function_id = $function?->id;
        $user->save();

        return $user;
    }

    public function test_every_module_points_to_existing_routes(): void
    {
        $routeNames = array_keys(Route::getRoutes()->getRoutesByName());

        foreach (config('school_permissions.modules') as $key => $module) {
            $this->assertTrue(Route::has($module['home']), "Route d'accueil introuvable pour le module {$key}");

            foreach ($module['links'] ?? [] as $link) {
                $this->assertTrue(Route::has($link['route']), "Lien de menu {$link['route']} introuvable pour le module {$key}");
                $this->assertTrue(Str::is($module['routes'], $link['route']), "Le lien {$link['route']} sort du périmètre du module {$key}");
            }

            foreach ($module['routes'] as $pattern) {
                $this->assertNotEmpty(
                    array_filter($routeNames, fn ($name) => Str::is($pattern, $name)),
                    "Le motif {$pattern} du module {$key} ne correspond à aucune route"
                );
            }
        }

        foreach (config('school_permissions.suggested_functions') as $name => $permissions) {
            $this->assertEmpty(array_diff($permissions, array_keys(config('school_permissions.modules'))), "Module inconnu dans {$name}");
        }
    }

    public function test_staff_only_reaches_routes_of_granted_modules(): void
    {
        $school = $this->makeSchool('a');
        $staff = $this->makeUser($school, 'staff', $this->makeFunction($school, ['payments', 'financial']));

        $this->actingAs($staff)->get(route('app.payments.index'))->assertOk();
        $this->actingAs($staff)->get(route('app.financial.unpaid_by_class'))->assertOk();
        $this->actingAs($staff)->get(route('app.staff-profile.edit'))->assertOk();

        // Modules non accordés et pages réservées à l'admin
        foreach (['app.students.index', 'app.dashboard', 'app.report-cards.index', 'app.settings.sms', 'app.settings.documents',
            'app.regulations.index', 'app.staff.index', 'app.staff-functions.index', 'app.profile.edit', 'canteen.rates.index'] as $route) {
            $this->actingAs($staff)->get(route($route))->assertForbidden();
        }

        $this->actingAs($staff)->get(route('teacher.dashboard'))->assertForbidden();
    }

    public function test_legacy_roles_are_migrated_to_equivalent_functions(): void
    {
        $schoolA = $this->makeSchool('a');
        $schoolB = $this->makeSchool('b');
        $accountantA = $this->makeUser($schoolA, 'accountant');
        $accountantB = $this->makeUser($schoolB, 'accountant');
        $canteen = $this->makeUser($schoolA, 'canteen_manager');
        $transport = $this->makeUser($schoolA, 'transport_manager');
        // Une école qui avait déjà sa fonction « Comptable » la garde (pas de doublon)
        $existing = $this->makeFunction($schoolB, ['payments'], 'Comptable');

        (require database_path('migrations/2026_10_29_120000_convert_legacy_staff_roles_to_functions.php'))->up();

        $this->assertSame(['extras_canteen'], $canteen->refresh()->staffModules());
        $this->assertSame('Responsable cantine', $canteen->staffFunction->name);
        $this->assertSame(['transport'], $transport->refresh()->staffModules());
        $this->actingAs($canteen)->get(route('extras.attendances.scan'))->assertOk();
        $this->actingAs($canteen)->get(route('extras.transport.vehicles.index'))->assertForbidden();
        $this->actingAs($transport)->get(route('extras.transport.routes.index'))->assertOk();
        $this->actingAs($transport)->get(route('extras.menus.index'))->assertForbidden();

        $accountantA->refresh();
        $accountantB->refresh();
        $this->assertTrue($accountantA->isStaff());
        $this->assertSame(['enrollments', 'payments', 'extras_billing'], $accountantA->staffModules());
        $this->assertSame($schoolA->id, $accountantA->staffFunction->school_id);
        $this->assertSame($existing->id, $accountantB->staff_function_id);
        $this->assertSame(1, StaffFunction::where('school_id', $schoolB->id)->count());

        // Même périmètre qu'avant : scolarité + facturation des extras, rien d'autre
        foreach (['app.enrollments.index', 'app.payments.index', 'extras.subscriptions.index', 'extras.payments.index', 'extras.refunds.index'] as $route) {
            $this->actingAs($accountantA)->get(route($route))->assertOk();
        }
        foreach (['app.students.index', 'extras.catalogue.index', 'extras.attendances.index'] as $route) {
            $this->actingAs($accountantA)->get(route($route))->assertForbidden();
        }
    }

    public function test_legacy_roles_no_longer_open_anything(): void
    {
        $school = $this->makeSchool('a');

        $this->actingAs($this->makeUser($school, 'accountant'))->get(route('app.payments.index'))->assertForbidden();
        $this->actingAs($this->makeUser($school, 'canteen_manager'))->get(route('extras.attendances.index'))->assertForbidden();
        $this->actingAs($this->makeUser($school, 'transport_manager'))->get(route('extras.transport.vehicles.index'))->assertForbidden();
    }

    public function test_staff_lands_on_first_granted_module(): void
    {
        $school = $this->makeSchool('a');
        $staff = $this->makeUser($school, 'staff', $this->makeFunction($school, ['report_cards', 'students']));

        $this->assertSame('app.report-cards.index', $staff->dashboardRouteName());

        $noModule = $this->makeUser($school, 'staff', $this->makeFunction($school, [], 'Vide'));
        $this->assertSame('app.staff-profile.edit', $noModule->dashboardRouteName());
    }

    public function test_function_from_another_school_grants_nothing(): void
    {
        $schoolA = $this->makeSchool('a');
        $schoolB = $this->makeSchool('b');
        $staff = $this->makeUser($schoolA, 'staff', $this->makeFunction($schoolB, ['payments']));

        $this->assertSame([], $staff->staffModules());
        $this->actingAs($staff)->get(route('app.payments.index'))->assertForbidden();
    }

    public function test_admin_cannot_assign_function_of_another_school(): void
    {
        $schoolA = $this->makeSchool('a');
        $schoolB = $this->makeSchool('b');
        $admin = $this->makeUser($schoolA, 'school_admin');
        $foreignFunction = $this->makeFunction($schoolB, ['payments']);

        $this->actingAs($admin)->post(route('app.staff.store'), [
            'first_name' => 'X', 'last_name' => 'Y', 'email' => 'x@example.test', 'gender' => 'M',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
            'staff_function_id' => $foreignFunction->id,
        ])->assertSessionHasErrors('staff_function_id');
    }

    public function test_admin_creates_function_and_member(): void
    {
        $school = $this->makeSchool('a');
        $admin = $this->makeUser($school, 'school_admin');

        $this->actingAs($admin)->post(route('app.staff-functions.store'), [
            'name' => 'Censeur', 'permissions' => ['students', 'attendances'],
        ])->assertRedirect(route('app.staff-functions.index'));

        $this->actingAs($admin)->post(route('app.staff-functions.store'), [
            'name' => 'Pirate', 'permissions' => ['superadmin'],
        ])->assertSessionHasErrors('permissions.0');

        $function = StaffFunction::where('name', 'Censeur')->firstOrFail();
        $this->assertSame($school->id, $function->school_id);

        $this->actingAs($admin)->post(route('app.staff.store'), [
            'first_name' => 'Awa', 'last_name' => 'Koné', 'email' => 'awa@example.test', 'gender' => 'F',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
            'staff_function_id' => $function->id,
        ])->assertRedirect(route('app.staff.index'));

        $member = User::where('email', 'awa@example.test')->firstOrFail();
        $this->assertTrue($member->isStaff());
        $this->assertSame($school->id, $member->school_id);
        $this->assertSame(['students', 'attendances'], $member->staffModules());
    }

    public function test_suggested_functions_are_created_once_for_current_school(): void
    {
        $school = $this->makeSchool('a');
        $admin = $this->makeUser($school, 'school_admin');

        $this->actingAs($admin)->post(route('app.staff-functions.suggested'));
        $this->actingAs($admin)->post(route('app.staff-functions.suggested'));

        $this->assertSame(
            count(config('school_permissions.suggested_functions')),
            StaffFunction::where('school_id', $school->id)->count()
        );
    }

    public function test_function_with_members_cannot_be_deleted(): void
    {
        $school = $this->makeSchool('a');
        $admin = $this->makeUser($school, 'school_admin');
        $function = $this->makeFunction($school, ['payments']);
        $this->makeUser($school, 'staff', $function);

        $this->actingAs($admin)->delete(route('app.staff-functions.destroy', $function))->assertSessionHasErrors('function');
        $this->assertModelExists($function);
    }
}
