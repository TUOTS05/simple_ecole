<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolRegulation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolRegulationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(School $school, string $role, string $suffix = ''): User
    {
        $user = new User([
            'first_name' => 'Test',
            'last_name' => $role.$suffix,
            'email' => $role.$suffix.'-'.$school->slug.'@example.test',
            'password' => bcrypt('password'),
        ]);
        $user->role = $role;
        $user->school_id = $school->id;
        $user->save();

        return $user;
    }

    private function makeSchool(string $slug): School
    {
        return School::create(['name' => 'École '.$slug, 'slug' => $slug, 'status' => 'active']);
    }

    public function test_publishing_creates_successive_versions(): void
    {
        $school = $this->makeSchool('a');
        $admin = $this->makeUser($school, 'school_admin');

        foreach (['Texte v1', 'Texte v2'] as $content) {
            $this->actingAs($admin)
                ->post(route('app.regulations.store'), ['title' => 'Règlement', 'content' => $content, 'requires_acceptance' => '1'])
                ->assertRedirect(route('app.regulations.index'));
        }

        $current = SchoolRegulation::currentFor($school->id);
        $this->assertSame(2, $current->version);
        $this->assertSame('Texte v2', $current->content);
    }

    public function test_parent_accepts_current_version_and_must_accept_again_after_update(): void
    {
        $school = $this->makeSchool('a');
        $admin = $this->makeUser($school, 'school_admin');
        $parent = $this->makeUser($school, 'parent');

        $this->actingAs($admin)->post(route('app.regulations.store'), ['title' => 'R', 'content' => 'v1', 'requires_acceptance' => '1']);
        $this->assertNotNull(SchoolRegulation::pendingFor($parent));

        $this->actingAs($parent)
            ->post(route('parent.regulation.accept'), ['version' => 1])
            ->assertRedirect(route('parent.regulation.show'));
        $this->assertNull(SchoolRegulation::pendingFor($parent));

        $this->actingAs($admin)->post(route('app.regulations.store'), ['title' => 'R', 'content' => 'v2', 'requires_acceptance' => '1']);
        $this->assertSame(2, SchoolRegulation::pendingFor($parent)?->version);

        // Accepter en envoyant une version périmée n'enregistre rien
        $this->actingAs($parent)->post(route('parent.regulation.accept'), ['version' => 1]);
        $this->assertNotNull(SchoolRegulation::pendingFor($parent));
    }

    public function test_parent_page_is_not_captured_by_student_route(): void
    {
        $school = $this->makeSchool('a');
        $parent = $this->makeUser($school, 'parent');

        $this->actingAs($parent)->get(route('parent.regulation.show'))->assertOk()->assertSee('pas encore publié');
    }

    public function test_admin_cannot_see_another_schools_regulation(): void
    {
        $schoolA = $this->makeSchool('a');
        $schoolB = $this->makeSchool('b');
        $adminB = $this->makeUser($schoolB, 'school_admin');

        $this->actingAs($this->makeUser($schoolA, 'school_admin'))
            ->post(route('app.regulations.store'), ['title' => 'R', 'content' => 'A']);
        $regulationA = SchoolRegulation::currentFor($schoolA->id);

        $this->actingAs($adminB)->get(route('app.regulations.show', $regulationA))->assertForbidden();
    }

    public function test_teacher_cannot_publish(): void
    {
        $school = $this->makeSchool('a');

        $this->actingAs($this->makeUser($school, 'teacher'))
            ->post(route('app.regulations.store'), ['title' => 'R', 'content' => 'x'])
            ->assertForbidden();
    }
}
