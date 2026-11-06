<?php

namespace Tests\Feature;

use App\Models\School;
use App\Services\SchoolSetupChecklist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Retour du cahier de test : « définir un flow à suivre » pour paramétrer l'école.
 */
class SchoolSetupChecklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_school_starts_with_every_step_to_do_in_order(): void
    {
        $school = School::create(['name' => 'École A', 'slug' => 'a', 'status' => 'active']);

        $steps = app(SchoolSetupChecklist::class)->stepsFor($school);

        $this->assertCount(10, $steps);
        $this->assertSame([], array_filter(array_column($steps, 'done')));
        $this->assertSame("Compléter le profil de l'école", $steps[0]['title']);
        $this->assertSame([false, false], array_column(array_slice($steps, -2), 'required'));
    }

    public function test_profile_step_is_done_once_address_and_phone_are_filled(): void
    {
        $school = School::create(['name' => 'École A', 'slug' => 'a', 'status' => 'active', 'address' => 'Cocody', 'phone' => '0700000000']);

        $this->assertTrue(app(SchoolSetupChecklist::class)->stepsFor($school)[0]['done']);
    }
}
