<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Régression : la création d'une école par le super-administrateur renvoyait une erreur 500
 * (variable du message de succès utilisée avant d'être définie, et règle de validation
 * « number » inexistante dès qu'un téléphone était saisi).
 */
class SuperAdminSchoolCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_creates_school_with_its_admin(): void
    {
        $superAdmin = new User(['first_name' => 'Super', 'last_name' => 'Admin', 'email' => 'super@example.test', 'password' => bcrypt('password')]);
        $superAdmin->role = 'super_admin';
        $superAdmin->save();

        $this->actingAs($superAdmin)
            ->post(route('superadmin.schools.store'), [
                'name' => 'École Les Palmiers',
                'school_type' => 'primaire',
                'phone' => '0707070707',
                'admin_name' => 'Awa Koné',
                'admin_email' => 'directrice@example.test',
                'admin_password' => 'motdepasse',
            ])
            ->assertRedirect(route('superadmin.schools.index'))
            ->assertSessionHas('success');

        $school = School::where('name', 'École Les Palmiers')->firstOrFail();
        $this->assertSame('0707070707', $school->phone);
        $this->assertDatabaseHas('users', ['email' => 'directrice@example.test', 'role' => 'school_admin', 'school_id' => $school->id]);
    }
}
