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

    public function test_invalid_phone_returns_a_french_validation_error(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.schools.store'), $this->payload(['phone' => 'abc']))
            ->assertSessionHasErrors(['phone' => 'Le numéro de téléphone ne doit contenir que des chiffres (8 minimum), éventuellement précédés de +.']);

        $this->assertDatabaseMissing('schools', ['slug' => 'groupe-scolaire-tts']);
    }
}
