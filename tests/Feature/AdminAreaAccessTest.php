<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Régression : jusqu'au 29/10/2026, le groupe de routes /app était ouvert aux rôles teacher et
 * parent, qui pouvaient lire les identifiants SMS, modifier le profil de l'école et lister les
 * élèves, enseignants, bulletins et paiements. Ils ont leurs propres espaces (/teacher, /parent).
 */
class AdminAreaAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(School $school, string $role): User
    {
        $user = new User([
            'first_name' => 'Test',
            'last_name' => $role,
            'email' => $role.'@example.test',
            'password' => bcrypt('password'),
        ]);
        $user->role = $role;
        $user->school_id = $school->id;
        $user->save();

        return $user;
    }

    public function test_teacher_and_parent_are_kept_out_of_admin_area(): void
    {
        $school = School::create(['name' => 'École A', 'slug' => 'a', 'status' => 'active']);

        foreach (['teacher', 'parent'] as $role) {
            $user = $this->makeUser($school, $role);

            foreach (['app.settings.sms', 'app.profile.edit', 'app.students.index', 'app.teachers.index',
                'app.report-cards.index', 'app.payments.index', 'app.enrollments.index', 'app.messages.index'] as $route) {
                $this->actingAs($user)->get(route($route))->assertForbidden();
            }
        }

        $this->actingAs($this->makeUser($school, 'school_admin'))->get(route('app.students.index'))->assertOk();
    }
}
