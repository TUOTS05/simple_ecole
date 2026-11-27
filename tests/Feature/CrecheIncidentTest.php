<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CareIncident;
use App\Models\CareStaffAssignment;
use App\Models\DevelopmentObservation;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Module Crèche — lot 4 : incidents (déclaration, validation, figement, suivis, annulation)
 * et suivi du développement.
 */
class CrecheIncidentTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $auxiliary;

    private User $manager;

    private SchoolClass $babies;

    private Student $adam;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-27 11:00:00');

        $this->school = School::create(['name' => 'Les Mirabelles', 'slug' => 'les-mirabelles', 'status' => 'active', 'structures' => ['creche']]);
        SchoolYear::create(['school_id' => $this->school->id, 'name' => '2026-2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-01', 'is_active' => true]);
        $this->babies = $this->makeGroup('Bébés');
        $this->auxiliary = $this->makeUser('care_staff', 'aux@example.test');
        $this->manager = $this->makeUser('care_manager', 'resp@example.test');
        CareStaffAssignment::create(['school_id' => $this->school->id, 'school_class_id' => $this->babies->id, 'user_id' => $this->auxiliary->id]);
        $this->adam = $this->makeChild($this->babies, 'Adam');
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

    private function makeGroup(string $name): SchoolClass
    {
        return SchoolClass::create(['school_id' => $this->school->id, 'name' => $name, 'level' => 'Bébés (0-1 an)', 'cycle' => 'creche']);
    }

    private function makeChild(SchoolClass $group, string $firstName): Student
    {
        $student = new Student(['first_name' => $firstName, 'last_name' => 'Koné', 'birth_date' => '2025-06-01', 'gender' => 'M']);
        $student->school_id = $this->school->id;
        $student->save();
        $student->classes()->attach($group->id);

        return $student;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'student_id' => $this->adam->id, 'occurred_on' => '2026-11-27', 'occurred_at' => '10:20', 'type' => 'fall',
            'description' => 'Est tombé en courant dans la salle de jeux, bosse au front.',
            'action_taken' => 'Froid appliqué, surveillance 30 min.', 'person_informed' => 'La mère, par téléphone', 'informed_at' => '2026-11-27T10:40',
        ], $overrides);
    }

    private function declare(array $overrides = []): CareIncident
    {
        $this->actingAs($this->auxiliary)->post(route('app.creche.incidents.store'), $this->payload($overrides))->assertSessionHasNoErrors();

        return CareIncident::latest('id')->firstOrFail();
    }

    // ------------------------------------------------------------------
    // Incidents
    // ------------------------------------------------------------------

    public function test_auxiliary_declares_an_incident_that_awaits_validation(): void
    {
        $this->actingAs($this->auxiliary)->get(route('app.creche.incidents.create', ['student' => $this->adam->id]))
            ->assertOk()->assertSee('Déclarer un incident')->assertSee('Adam');

        $response = $this->actingAs($this->auxiliary)->post(route('app.creche.incidents.store'), $this->payload());
        $incident = CareIncident::firstOrFail();
        $response->assertRedirect(route('app.creche.incidents.show', $incident));

        $this->assertSame('declared', $incident->status);
        $this->assertSame($this->auxiliary->id, $incident->reported_by);
        $this->assertSame($this->babies->id, $incident->school_class_id);
        $this->assertSame('10:20', $incident->timeLabel());
        $this->assertSame('2026-11-27 10:40', $incident->informed_at->format('Y-m-d H:i'));

        $log = ActivityLog::where('action', 'creche.incident.declared')->firstOrFail();
        $this->assertSame('CareIncident', $log->subject_type);
        $this->assertSame($incident->id, $log->subject_id);
        $this->assertSame($this->adam->id, $log->meta['student_id']);

        // Visible dans la liste (en attente), dans la journée de l'enfant, et signalé à la responsable
        $this->actingAs($this->manager)->get(route('app.creche.incidents.index'))
            ->assertOk()->assertSee('1 incident(s) à valider')->assertSee('Chute');
        $this->actingAs($this->auxiliary)->get(route('app.creche.journal.child', $this->adam))
            ->assertOk()->assertSee('Incident : Chute')->assertSee('À valider');
        $this->actingAs($this->manager)->get(route('app.creche.incidents.show', $incident))
            ->assertOk()->assertSee('Valider l\'incident', false);
        $this->actingAs($this->auxiliary)->get(route('app.creche.incidents.show', $incident))
            ->assertOk()->assertDontSee('Valider l&#039;incident', false);
    }

    public function test_declared_incident_can_be_corrected_then_is_frozen_once_validated(): void
    {
        $incident = $this->declare();

        // Correction par la personne qui l'a déclaré, tracée avant/après
        $this->actingAs($this->auxiliary)->put(route('app.creche.incidents.update', $incident), $this->payload(['type' => 'injury', 'description' => 'Petite coupure au doigt.']))
            ->assertSessionHasNoErrors();
        $this->assertSame('injury', $incident->fresh()->type);
        $update = ActivityLog::where('action', 'creche.incident.updated')->firstOrFail();
        $this->assertSame('fall', $update->meta['before']['type']);
        $this->assertSame('injury', $update->meta['after']['type']);

        // L'auxiliaire ne valide pas
        $this->actingAs($this->auxiliary)->post(route('app.creche.incidents.validate', $incident))->assertForbidden();
        $this->actingAs($this->auxiliary)->post(route('app.creche.incidents.cancel', $incident), ['reason' => 'x'])->assertForbidden();

        // Validation par la responsable
        $this->actingAs($this->manager)->post(route('app.creche.incidents.validate', $incident), ['comment' => 'Parents rappelés à 12h.'])
            ->assertRedirect(route('app.creche.incidents.show', $incident));
        $incident->refresh();
        $this->assertSame('validated', $incident->status);
        $this->assertSame($this->manager->id, $incident->validated_by);
        $this->assertSame('Parents rappelés à 12h.', $incident->validation_comment);

        // Figé : plus de modification, ni par l'auteur ni par la responsable ; pas de double validation
        $this->actingAs($this->auxiliary)->get(route('app.creche.incidents.edit', $incident))->assertForbidden();
        $this->actingAs($this->manager)->put(route('app.creche.incidents.update', $incident), $this->payload(['description' => 'Réécrit']))
            ->assertSessionHasErrors('incident');
        $this->actingAs($this->manager)->post(route('app.creche.incidents.validate', $incident))->assertSessionHasErrors('incident');
        $this->assertSame('Petite coupure au doigt.', $incident->fresh()->description);

        // Un suivi peut toujours être ajouté (ajout seul)
        $this->actingAs($this->auxiliary)->post(route('app.creche.incidents.follow-up', $incident), ['text' => 'Pansement changé à 15h.'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Pansement changé à 15h.', $incident->fresh()->follow_ups[0]['text']);

        $this->actingAs($this->manager)->get(route('app.creche.incidents.show', $incident))
            ->assertOk()->assertSee('VALIDÉ')->assertSee('Pansement changé à 15h.')->assertSee('Historique (4)');
    }

    public function test_wrong_declaration_is_cancelled_with_a_reason_but_kept(): void
    {
        $incident = $this->declare();

        $this->actingAs($this->manager)->post(route('app.creche.incidents.cancel', $incident), [])->assertSessionHasErrors('reason');
        $this->actingAs($this->manager)->post(route('app.creche.incidents.cancel', $incident), ['reason' => 'Déclaré sur le mauvais enfant'])
            ->assertSessionHasNoErrors();

        $incident->refresh();
        $this->assertSame('cancelled', $incident->status);
        $this->assertSame(1, CareIncident::count());
        $this->actingAs($this->auxiliary)->post(route('app.creche.incidents.follow-up', $incident), ['text' => 'x'])->assertSessionHasErrors('incident');
        $this->actingAs($this->auxiliary)->get(route('app.creche.journal.child', $this->adam))->assertDontSee('Incident : Chute');
    }

    public function test_incident_form_is_validated_and_limited_to_own_groups(): void
    {
        $this->actingAs($this->auxiliary)->post(route('app.creche.incidents.store'), $this->payload([
            'occurred_on' => '2026-11-28', 'type' => 'explosion', 'description' => '',
        ]))->assertSessionHasErrors(['occurred_on', 'type', 'description']);
        $this->actingAs($this->auxiliary)->post(route('app.creche.incidents.store'), $this->payload(['occurred_at' => '12:00']))
            ->assertSessionHasErrors('occurred_at');

        $sarah = $this->makeChild($this->makeGroup('Petits'), 'Sarah');
        $this->actingAs($this->auxiliary)->post(route('app.creche.incidents.store'), $this->payload(['student_id' => $sarah->id]))->assertForbidden();

        // Une autre auxiliaire du même groupe ne modifie pas la déclaration d'une collègue
        $incident = $this->declare();
        $colleague = $this->makeUser('care_staff', 'aux2@example.test');
        CareStaffAssignment::create(['school_id' => $this->school->id, 'school_class_id' => $this->babies->id, 'user_id' => $colleague->id]);
        $this->actingAs($colleague)->put(route('app.creche.incidents.update', $incident), $this->payload(['description' => 'x']))->assertForbidden();

        // Autre école : introuvable
        $other = School::create(['name' => 'Autre', 'slug' => 'autre', 'status' => 'active', 'structures' => ['creche']]);
        $otherDirector = $this->makeUser('school_admin', 'dir2@example.test', $other);
        $this->actingAs($otherDirector)->get(route('app.creche.incidents.show', $incident))->assertNotFound();
        $this->actingAs($otherDirector)->post(route('app.creche.incidents.validate', $incident))->assertNotFound();
        $this->actingAs($otherDirector)->get(route('app.creche.incidents.index'))->assertOk()->assertDontSee('Adam');
        $this->assertSame('declared', $incident->fresh()->status);
    }

    // ------------------------------------------------------------------
    // Développement
    // ------------------------------------------------------------------

    public function test_development_observations_by_domain(): void
    {
        $this->actingAs($this->auxiliary)->post(route('app.creche.development.store', $this->adam), [
            'observed_on' => '2026-11-20', 'domain' => 'Motricité', 'level' => 'in_progress', 'notes' => 'Se tient debout avec appui',
        ])->assertSessionHasNoErrors();
        $this->actingAs($this->auxiliary)->post(route('app.creche.development.store', $this->adam), [
            'observed_on' => '2026-11-27', 'domain' => 'Motricité', 'level' => 'observed', 'notes' => 'Premiers pas seul',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->auxiliary)->get(route('app.creche.development', $this->adam))
            ->assertOk()->assertSee('Premiers pas seul')->assertSee('Se tient debout avec appui')->assertSee('Pas encore observé');
        $latest = $this->actingAs($this->auxiliary)->get(route('app.creche.development', $this->adam))->viewData('latest');
        $this->assertSame('observed', $latest['Motricité']->level);

        $this->actingAs($this->auxiliary)->post(route('app.creche.development.store', $this->adam), [
            'observed_on' => '2026-11-28', 'domain' => 'Natation', 'level' => 'note sur 20',
        ])->assertSessionHasErrors(['observed_on', 'domain', 'level']);

        $this->assertSame(2, ActivityLog::where('action', 'creche.development.added')->count());

        // Retrait : l'auteur le jour même, la responsable à tout moment
        $first = DevelopmentObservation::where('notes', 'Se tient debout avec appui')->firstOrFail();
        Carbon::setTestNow('2026-11-28 09:00:00');
        $this->actingAs($this->auxiliary)->delete(route('app.creche.development.destroy', $first))->assertForbidden();
        $this->actingAs($this->manager)->delete(route('app.creche.development.destroy', $first))->assertRedirect();
        $this->assertSoftDeleted($first);
        $this->assertSame($this->manager->id, DevelopmentObservation::withTrashed()->find($first->id)->deleted_by);

        // Autre groupe : refusé
        $sarah = $this->makeChild($this->makeGroup('Petits'), 'Sarah');
        $this->actingAs($this->auxiliary)->get(route('app.creche.development', $sarah))->assertForbidden();
    }

    public function test_settings_store_development_domains_and_parent_visibility(): void
    {
        $this->actingAs($this->manager)->put(route('app.creche.settings.update'), [
            'levels' => 'Bébés (0-1 an)', 'opening_time' => '07:00', 'closing_time' => '18:00',
            'development_domains' => "Motricité\nLangage\nJeu symbolique", 'incidents_parent_visibility' => 'none',
        ])->assertSessionHasNoErrors();

        $school = $this->school->fresh();
        $this->assertSame(['Motricité', 'Langage', 'Jeu symbolique'], $school->setting('creche.development_domains'));
        $this->assertSame('none', $school->setting('creche.incidents_parent_visibility'));
        // Les réglages absents du formulaire sont conservés
        $this->assertSame('12:00', $school->setting('creche.meal_time'));

        $this->actingAs($this->auxiliary)->post(route('app.creche.development.store', $this->adam), [
            'observed_on' => '2026-11-27', 'domain' => 'Jeu symbolique', 'level' => 'to_stimulate',
        ])->assertSessionHasNoErrors();
    }
}
