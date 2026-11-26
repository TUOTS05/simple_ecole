<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminSchoolCreationTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $user = new User(['first_name' => 'Super', 'last_name' => 'Admin', 'email' => 'super@example.test', 'password' => bcrypt('password')]);
        $user->role = 'super_admin';
        $user->save();

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Groupe scolaire TTS',
            'school_type' => 'both',
            'address' => 'Abidjan Cocody',
            'phone' => '0153919175',
            'admin_name' => 'TUO T. SEYDOU',
            'admin_email' => 'directeur@example.test',
            'admin_password' => 'motdepasse123',
        ], $overrides);
    }

    public function test_super_admin_can_create_a_school_with_its_director(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.schools.store'), $this->payload())
            ->assertRedirect(route('superadmin.schools.index'))
            ->assertSessionHas('success');

        $school = School::where('slug', 'groupe-scolaire-tts')->firstOrFail();
        $this->assertSame('0153919175', $school->phone); // 0 initial conservé
        $this->assertSame('suspended', $school->status);

        $director = User::where('email', 'directeur@example.test')->firstOrFail();
        $this->assertSame('school_admin', $director->role);
        $this->assertSame($school->id, $director->school_id);
    }

    public function test_phone_accepts_international_format(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.schools.store'), $this->payload(['phone' => '+225 01 53 91 91 75']))
            ->assertSessionHasNoErrors();
    }

    private function editPayload(School $school, array $overrides = []): array
    {
        return array_merge([
            'name' => $school->name,
            'slug' => $school->slug,
            'school_type' => $school->school_type,
            'status' => 'active',
        ], $overrides);
    }

    public function test_edit_form_lists_plans_managed_by_super_admin(): void
    {
        $this->seed(\Database\Seeders\SubscriptionPlanSeeder::class);
        $school = School::create(['name' => 'Ecole Test', 'slug' => 'ecole-test', 'school_type' => 'both', 'status' => 'suspended']);

        $this->actingAs($this->superAdmin())
            ->get(route('superadmin.schools.edit', $school))
            ->assertOk()
            ->assertSee('Essentiel')->assertSee('Croissance')->assertSee('Premium')->assertSee('Sur Devis')
            ->assertDontSee('value="basic"', false);
    }

    public function test_updating_school_with_a_plan_applies_the_plan_limits(): void
    {
        $this->seed(\Database\Seeders\SubscriptionPlanSeeder::class);
        $school = School::create(['name' => 'Ecole Test', 'slug' => 'ecole-test', 'school_type' => 'both', 'status' => 'suspended', 'max_students' => 5]);

        $this->actingAs($this->superAdmin())
            ->put(route('superadmin.schools.update', $school), $this->editPayload($school, [
                'subscription_plan' => 'Croissance',
                'subscription_start_date' => now()->toDateString(),
                'subscription_end_date' => now()->addYear()->toDateString(),
                'max_students' => 999999, // ignoré : les plafonds viennent du forfait
            ]))
            ->assertRedirect(route('superadmin.schools.index'))
            ->assertSessionHasNoErrors();

        $school->refresh();
        $this->assertSame('Croissance', $school->subscription_plan);
        $this->assertSame(250, (int) $school->max_students);
        $this->assertSame(5, (int) $school->max_users);
        $this->assertSame('active', $school->status);
    }

    public function test_unknown_plan_is_rejected_and_school_without_plan_can_be_edited(): void
    {
        $this->seed(\Database\Seeders\SubscriptionPlanSeeder::class);
        $school = School::create(['name' => 'Ecole Test', 'slug' => 'ecole-test', 'school_type' => 'both', 'status' => 'suspended']);
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->put(route('superadmin.schools.update', $school), $this->editPayload($school, [
                'subscription_plan' => 'premium-inexistant',
                'subscription_start_date' => now()->toDateString(),
                'subscription_end_date' => now()->addYear()->toDateString(),
            ]))
            ->assertSessionHasErrors(['subscription_plan' => 'Ce forfait n\'existe pas. Choisissez un forfait de la liste.']);

        // Sans forfait : pas de dates exigées
        $this->actingAs($admin)
            ->put(route('superadmin.schools.update', $school), $this->editPayload($school, [
                'name' => 'Ecole Renommée', 'status' => 'suspended',
                'subscription_plan' => '', 'subscription_start_date' => '', 'subscription_end_date' => '',
            ]))
            ->assertSessionHasNoErrors();
        $this->assertSame('Ecole Renommée', $school->fresh()->name);
    }

    public function test_invalid_phone_returns_a_french_validation_error(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.schools.store'), $this->payload(['phone' => 'abc']))
            ->assertSessionHasErrors(['phone' => 'Le numéro de téléphone ne doit contenir que des chiffres (8 minimum), éventuellement précédés de +.']);

        $this->assertDatabaseMissing('schools', ['slug' => 'groupe-scolaire-tts']);
    }
}
