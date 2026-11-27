<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CareEvent;
use App\Models\CareStaffAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Module Crèche — lot 3 : journal quotidien (biberons, repas, sommeil, changes, hygiène,
 * activités, observations), mode groupe, espace auxiliaire et chronologie de la journée.
 */
class CrecheJournalTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $auxiliary;

    private SchoolClass $babies;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-27 10:30:00');

        $this->school = School::create(['name' => 'Les Mirabelles', 'slug' => 'les-mirabelles', 'status' => 'active', 'structures' => ['creche']]);
        SchoolYear::create(['school_id' => $this->school->id, 'name' => '2026-2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-01', 'is_active' => true]);
        $this->babies = $this->makeGroup('Bébés');
        $this->auxiliary = $this->makeUser('care_staff', 'aux@example.test');
        CareStaffAssignment::create(['school_id' => $this->school->id, 'school_class_id' => $this->babies->id, 'user_id' => $this->auxiliary->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeUser(string $role, string $email, ?School $school = null): User
    {
        $user = new User(['first_name' => 'Awa', 'last_name' => $role, 'email' => $email, 'password' => bcrypt('password')]);
        $user->role = $role;
        $user->school_id = ($school ?? $this->school)->id;
        $user->save();

        return $user;
    }

    private function makeGroup(string $name, ?School $school = null): SchoolClass
    {
        return SchoolClass::create(['school_id' => ($school ?? $this->school)->id, 'name' => $name, 'level' => 'Bébés (0-1 an)', 'cycle' => 'creche']);
    }

    private function makeChild(SchoolClass $group, string $firstName): Student
    {
        $student = new Student(['first_name' => $firstName, 'last_name' => 'Koné', 'birth_date' => '2025-06-01', 'gender' => 'M']);
        $student->school_id = $group->school_id;
        $student->save();
        $student->classes()->attach($group->id);

        return $student;
    }

    /** Enfant arrivé ce matin. */
    private function presentChild(string $firstName, string $arrival = '07:45'): Student
    {
        $child = $this->makeChild($this->babies, $firstName);
        $this->actingAs($this->auxiliary)->post(route('app.creche.arrival', $child), ['time' => $arrival])->assertSessionHasNoErrors();

        return $child;
    }

    private function record(string $type, array $children, array $fields): TestResponse
    {
        return $this->actingAs($this->auxiliary)->post(route('app.creche.journal.store'), array_merge([
            'event_type' => $type, 'group' => $this->babies->id, 'student_ids' => array_map(fn ($c) => $c->id, $children),
        ], $fields));
    }

    // ------------------------------------------------------------------
    // Saisie
    // ------------------------------------------------------------------

    public function test_bottle_is_recorded_with_its_consumption(): void
    {
        $adam = $this->presentChild('Adam');

        $this->actingAs($this->auxiliary)->get(route('app.creche.journal.create', ['type' => 'bottle', 'group' => $this->babies->id, 'student' => $adam->id]))
            ->assertOk()->assertSee('Quantité préparée');

        $this->record('bottle', [$adam], ['start_time' => '08:15', 'quantity' => 150, 'consumed' => 120, 'milk' => 'Lait 2e âge'])
            ->assertRedirect(route('app.creche.journal', ['group' => $this->babies->id]))->assertSessionHasNoErrors();

        $event = CareEvent::firstOrFail();
        $this->assertSame('bottle', $event->event_type);
        $this->assertSame('2026-11-27', $event->event_date->toDateString());
        $this->assertSame('08:15', $event->startLabel());
        $this->assertSame(150.0, $event->quantity);
        $this->assertSame('150 ml · Lait 2e âge · bu 120 ml (80 %)', $event->summary());
        $this->assertSame($this->auxiliary->id, $event->staff_id);
        $this->assertSame($this->babies->id, $event->school_class_id);
        $this->assertNotEmpty($event->uuid);

        $log = ActivityLog::where('action', 'creche.journal.added')->firstOrFail();
        $this->assertSame($adam->id, $log->subject_id);
        $this->assertSame($event->id, $log->meta['event_id']);

        // Validation : quantité bue > préparée, quantité absente, heure dans le futur
        $this->record('bottle', [$adam], ['start_time' => '09:00', 'quantity' => 100, 'consumed' => 150])->assertSessionHasErrors('consumed');
        $this->record('bottle', [$adam], ['start_time' => '09:00'])->assertSessionHasErrors('quantity');
        $this->record('bottle', [$adam], ['start_time' => '11:00', 'quantity' => 100])->assertSessionHasErrors('start_time');
        $this->assertSame(1, CareEvent::count());
    }

    public function test_group_mode_creates_one_editable_event_per_child(): void
    {
        $adam = $this->presentChild('Adam');
        $sarah = $this->presentChild('Sarah');
        $lina = $this->presentChild('Lina');

        // Repas : tous les présents sont cochés par défaut
        $this->actingAs($this->auxiliary)->get(route('app.creche.journal.create', ['type' => 'meal', 'group' => $this->babies->id]))
            ->assertOk()->assertSee('Adam')->assertSee('Sarah')->assertSee('Lina')->assertSee('checked', false);

        $this->record('meal', [$adam, $sarah, $lina], ['start_time' => '10:15', 'menu' => 'Purée de carottes', 'appreciation' => 'all'])
            ->assertSessionHasNoErrors()->assertSessionHas('success', '✅ Repas enregistré pour 3 enfants.');
        $this->assertSame(3, CareEvent::where('event_type', 'meal')->count());

        // Chaque événement reste modifiable individuellement
        $sarahMeal = CareEvent::where('student_id', $sarah->id)->firstOrFail();
        $this->actingAs($this->auxiliary)->put(route('app.creche.journal.update', $sarahMeal), [
            'start_time' => '10:15', 'menu' => 'Purée de carottes', 'appreciation' => 'little', 'notes' => 'Fatiguée',
        ])->assertSessionHasNoErrors();

        $this->assertSame('little', $sarahMeal->fresh()->datum('appreciation'));
        $this->assertSame('all', CareEvent::where('student_id', $adam->id)->first()->datum('appreciation'));
        $this->assertSame('Purée de carottes · Peu mangé', $sarahMeal->fresh()->summary());

        $update = ActivityLog::where('action', 'creche.journal.updated')->firstOrFail();
        $this->assertStringContainsString('Tout mangé', $update->meta['before']['summary']);
        $this->assertStringContainsString('Peu mangé', $update->meta['after']['summary']);

        // Pas d'enfant coché : refusé
        $this->record('meal', [], ['start_time' => '10:15', 'appreciation' => 'all'])->assertSessionHasErrors('student_ids');
    }

    public function test_sleep_in_progress_then_wake_up(): void
    {
        $adam = $this->presentChild('Adam');

        $this->record('sleep', [$adam], ['start_time' => '09:40'])->assertSessionHasNoErrors();
        $sleep = CareEvent::firstOrFail();
        $this->assertTrue($sleep->isSleeping());

        $this->actingAs($this->auxiliary)->get(route('app.creche.journal'))
            ->assertOk()->assertSee('1 enfant dort')->assertSee('depuis 09:40');

        Carbon::setTestNow('2026-11-27 11:25:00');
        $this->actingAs($this->auxiliary)->post(route('app.creche.journal.wake', $sleep))->assertSessionHasNoErrors();

        $sleep->refresh();
        $this->assertFalse($sleep->isSleeping());
        $this->assertSame(105, $sleep->durationMinutes());
        $this->assertSame('1h45', CareEvent::formatDuration($sleep->durationMinutes()));
        $this->actingAs($this->auxiliary)->post(route('app.creche.journal.wake', $sleep))->assertStatus(422);

        // Sieste complète saisie d'un coup, réveil avant le coucher refusé
        $this->record('sleep', [$adam], ['start_time' => '11:00', 'end_time' => '10:00'])->assertSessionHasErrors('end_time');
    }

    public function test_diaper_hygiene_activity_and_observation_use_configurable_lists(): void
    {
        $adam = $this->presentChild('Adam');

        $this->record('diaper', [$adam], ['start_time' => '09:20', 'kind' => 'stool', 'product' => 'Liniment'])->assertSessionHasNoErrors();
        $this->record('hygiene', [$adam], ['start_time' => '09:25', 'kind' => 'Lavage des mains'])->assertSessionHasNoErrors();
        $this->record('activity', [$adam], ['start_time' => '09:30', 'category' => 'Comptine', 'title' => 'Une souris verte', 'duration' => 20])->assertSessionHasNoErrors();
        $this->record('observation', [$adam], ['start_time' => '10:00', 'notes' => 'Premiers pas sans aide !'])->assertSessionHasNoErrors();

        $this->assertSame('Selles · Liniment', CareEvent::where('event_type', 'diaper')->first()->summary());
        $this->assertSame('Comptine · Une souris verte · 20 min', CareEvent::where('event_type', 'activity')->first()->summary());

        $this->record('activity', [$adam], ['start_time' => '10:00', 'category' => 'Natation'])->assertSessionHasErrors('category');
        $this->record('observation', [$adam], ['start_time' => '10:00'])->assertSessionHasErrors('notes');

        // Une catégorie ajoutée dans Crèche > Paramètres devient disponible
        $this->school->update(['settings' => ['creche' => ['activity_categories' => ['Natation', 'Jardinage']]]]);
        $this->record('activity', [$adam], ['start_time' => '10:10', 'category' => 'Natation'])->assertSessionHasNoErrors();
    }

    // ------------------------------------------------------------------
    // Journée de l'enfant et suppression
    // ------------------------------------------------------------------

    public function test_child_day_shows_a_chronological_timeline_and_deletions_stay_traceable(): void
    {
        $adam = $this->presentChild('Adam', '07:42');
        $this->record('bottle', [$adam], ['start_time' => '08:15', 'quantity' => 150]);
        $this->record('diaper', [$adam], ['start_time' => '09:20', 'kind' => 'urine']);
        $this->record('activity', [$adam], ['start_time' => '09:30', 'category' => 'Motricité']);

        $this->actingAs($this->auxiliary)->get(route('app.creche.journal.child', $adam))
            ->assertOk()
            ->assertSeeInOrder(['07h42', 'Arrivée', '08h15', 'Biberon', '150 ml', '09h20', 'Change', '09h30', 'Activité', 'Motricité'])
            ->assertSee('1 biberon(s)')->assertSee('1 change(s)');

        $diaper = CareEvent::where('event_type', 'diaper')->firstOrFail();
        $this->actingAs($this->auxiliary)->delete(route('app.creche.journal.destroy', $diaper))
            ->assertRedirect(route('app.creche.journal.child', ['student' => $adam, 'date' => '2026-11-27']));

        $this->assertSoftDeleted($diaper);
        $this->assertSame($this->auxiliary->id, CareEvent::withTrashed()->find($diaper->id)->deleted_by);
        $log = ActivityLog::where('action', 'creche.journal.deleted')->firstOrFail();
        $this->assertSame($adam->id, $log->subject_id);
        $this->assertSame('Urine', $log->meta['before']['summary']);

        $this->actingAs($this->auxiliary)->get(route('app.creche.journal.child', $adam))->assertDontSee('09h20');

        // Pas de journée dans le futur
        $this->actingAs($this->auxiliary)->get(route('app.creche.journal.child', ['student' => $adam, 'date' => '2026-11-28']))->assertNotFound();
    }

    // ------------------------------------------------------------------
    // À faire
    // ------------------------------------------------------------------

    public function test_board_lists_what_to_do_now(): void
    {
        $adam = $this->presentChild('Adam', '07:00');
        $sarah = $this->presentChild('Sarah', '09:00');
        $this->record('diaper', [$sarah], ['start_time' => '09:30', 'kind' => 'urine']);

        // 10:30 : Adam arrivé depuis 3h30 sans change ; Sarah changée il y a 1h ; repas pas encore dû
        $board = $this->actingAs($this->auxiliary)->get(route('app.creche.journal'));
        $board->assertOk()->assertSee('1 change(s) à prévoir')->assertDontSee('repas à renseigner');
        $this->assertSame(['Adam'], $board->viewData('todo')['diaper']->pluck('first_name')->values()->all());

        // 12:30 : repas à renseigner pour ceux qui n'ont pas mangé
        Carbon::setTestNow('2026-11-27 12:30:00');
        $this->record('meal', [$adam], ['start_time' => '12:00', 'appreciation' => 'all']);
        $this->actingAs($this->auxiliary)->get(route('app.creche.journal'))
            ->assertOk()->assertSee('repas à renseigner : Sarah');
    }

    // ------------------------------------------------------------------
    // Droits
    // ------------------------------------------------------------------

    public function test_only_staff_of_the_group_can_write_the_journal(): void
    {
        $adam = $this->presentChild('Adam');
        $toddlers = $this->makeGroup('Petits');
        $sarah = $this->makeChild($toddlers, 'Sarah');

        // Enfant d'un autre groupe glissé dans la saisie : tout est refusé
        $this->record('meal', [$adam, $sarah], ['start_time' => '10:00', 'appreciation' => 'all'])->assertForbidden();
        $this->assertSame(0, CareEvent::count());

        // L'accueil consulte mais ne saisit pas le journal
        $reception = $this->makeUser('reception', 'accueil@example.test');
        $this->actingAs($reception)->get(route('app.creche.journal.child', $adam))->assertOk()->assertDontSee('Modifier');
        $this->actingAs($reception)->post(route('app.creche.journal.store'), [
            'event_type' => 'diaper', 'group' => $this->babies->id, 'student_ids' => [$adam->id], 'start_time' => '10:00', 'kind' => 'urine',
        ])->assertForbidden();

        // Un événement d'un jour passé : corrigé par la responsable seulement
        $this->record('diaper', [$adam], ['start_time' => '10:00', 'kind' => 'urine']);
        $event = CareEvent::firstOrFail();
        Carbon::setTestNow('2026-11-28 09:00:00');
        $this->actingAs($this->auxiliary)->get(route('app.creche.journal.edit', $event))->assertForbidden();
        $manager = $this->makeUser('care_manager', 'resp@example.test');
        $this->actingAs($manager)->put(route('app.creche.journal.update', $event), ['start_time' => '10:05', 'kind' => 'mixed'])
            ->assertSessionHasNoErrors();
        $this->assertSame('mixed', $event->fresh()->datum('kind'));

        // Autre école : introuvable
        $other = School::create(['name' => 'Autre', 'slug' => 'autre', 'status' => 'active', 'structures' => ['creche']]);
        $otherDirector = $this->makeUser('school_admin', 'dir2@example.test', $other);
        $this->actingAs($otherDirector)->get(route('app.creche.journal.edit', $event))->assertNotFound();
        $this->actingAs($otherDirector)->delete(route('app.creche.journal.destroy', $event))->assertNotFound();
        $this->actingAs($otherDirector)->get(route('app.creche.journal.child', $adam))->assertNotFound();
        $this->assertNotSoftDeleted($event);
    }
}
