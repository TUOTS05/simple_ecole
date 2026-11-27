<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AuthorizedPickup;
use App\Models\CareDailySummary;
use App\Models\CareIncident;
use App\Models\CareStaffAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\SmsLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Module Crèche — lot 5 : carnet du jour publié par l'auxiliaire, « La journée de mon enfant »
 * dans l'espace parent et l'API mobile, notifications SMS aux parents.
 */
class CrecheParentTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $auxiliary;

    private User $manager;

    private User $parent;

    private SchoolClass $babies;

    private Student $adam;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-27 16:30:00');

        $this->school = School::create(['name' => 'Les Mirabelles', 'slug' => 'les-mirabelles', 'status' => 'active', 'structures' => ['creche']]);
        SchoolYear::create(['school_id' => $this->school->id, 'name' => '2026-2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-01', 'is_active' => true]);
        $this->babies = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Bébés', 'level' => 'Bébés (0-1 an)', 'cycle' => 'creche']);
        $this->auxiliary = $this->makeUser('care_staff', 'aux@example.test', 'Mariam', 'Diallo');
        $this->manager = $this->makeUser('care_manager', 'resp@example.test', 'Awa', 'Responsable');
        CareStaffAssignment::create(['school_id' => $this->school->id, 'school_class_id' => $this->babies->id, 'user_id' => $this->auxiliary->id]);

        $this->adam = $this->makeChild('Adam');
        $this->parent = $this->makeUser('parent', 'fatou@example.test', 'Fatou', 'Koné', '0707070707');
        $this->adam->parents()->attach($this->parent->id, ['school_id' => $this->school->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeUser(string $role, string $email, string $firstName, string $lastName, ?string $phone = null): User
    {
        $user = new User(['first_name' => $firstName, 'last_name' => $lastName, 'email' => $email, 'password' => bcrypt('password'), 'phone' => $phone]);
        $user->role = $role;
        $user->school_id = $this->school->id;
        $user->save();

        return $user;
    }

    private function makeChild(string $firstName): Student
    {
        $student = new Student(['first_name' => $firstName, 'last_name' => 'Koné', 'birth_date' => '2025-06-01', 'gender' => 'M']);
        $student->school_id = $this->school->id;
        $student->save();
        $student->classes()->attach($this->babies->id);

        return $student;
    }

    private function record(string $type, array $fields): void
    {
        $this->actingAs($this->auxiliary)->post(route('app.creche.journal.store'), array_merge([
            'event_type' => $type, 'group' => $this->babies->id, 'student_ids' => [$this->adam->id],
        ], $fields))->assertSessionHasNoErrors();
    }

    /** Une journée type : arrivée, biberons, repas, sieste, changes, activité, observation. */
    private function fillTheDay(): void
    {
        $this->actingAs($this->auxiliary)->post(route('app.creche.arrival', $this->adam), ['time' => '07:42'])->assertSessionHasNoErrors();
        $this->record('bottle', ['start_time' => '08:15', 'quantity' => 150, 'consumed' => 150]);
        $this->record('bottle', ['start_time' => '14:30', 'quantity' => 120, 'consumed' => 90]);
        $this->record('meal', ['start_time' => '11:30', 'menu' => 'Purée de carottes', 'appreciation' => 'all']);
        $this->record('sleep', ['start_time' => '12:15', 'end_time' => '14:00', 'quality' => 'good']);
        $this->record('diaper', ['start_time' => '09:20', 'kind' => 'urine']);
        $this->record('diaper', ['start_time' => '14:05', 'kind' => 'stool']);
        $this->record('activity', ['start_time' => '10:00', 'category' => 'Comptine', 'title' => 'Une souris verte']);
        $this->record('observation', ['start_time' => '15:00', 'notes' => 'A dit « mama » pour la première fois !']);
    }

    private function enableSms(array $events): void
    {
        $settings = ['creche' => collect($events)->mapWithKeys(fn ($on, $event) => ["notify_{$event}" => $on])->all()];
        $this->school->update(['sms_enabled' => true, 'settings' => $settings]);
    }

    // ------------------------------------------------------------------
    // Carnet du jour (côté personnel)
    // ------------------------------------------------------------------

    public function test_auxiliary_publishes_the_daily_summary(): void
    {
        $this->actingAs($this->auxiliary)->get(route('app.creche.journal.child', $this->adam))->assertOk()->assertSee('Publier aux parents');

        $this->actingAs($this->auxiliary)->post(route('app.creche.journal.summary', $this->adam), [
            'date' => '2026-11-27', 'mood' => 'very_good', 'message' => 'Adam a passé une très bonne journée.',
        ])->assertRedirect(route('app.creche.journal.child', ['student' => $this->adam, 'date' => '2026-11-27']));

        $summary = CareDailySummary::firstOrFail();
        $this->assertSame('very_good', $summary->mood);
        $this->assertSame($this->auxiliary->id, $summary->published_by);
        $this->assertSame(1, ActivityLog::where('action', 'creche.summary.published')->count());

        // Mise à jour : même carnet, tracée
        $this->actingAs($this->auxiliary)->post(route('app.creche.journal.summary', $this->adam), [
            'date' => '2026-11-27', 'mood' => 'good', 'message' => 'Un peu fatigué en fin de journée.',
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, CareDailySummary::count());
        $this->assertSame('good', $summary->fresh()->mood);
        $this->assertSame(1, ActivityLog::where('action', 'creche.summary.updated')->count());

        // Humeur obligatoire ; jour passé réservé à la responsable
        $this->actingAs($this->auxiliary)->post(route('app.creche.journal.summary', $this->adam), ['date' => '2026-11-27'])->assertSessionHasErrors('mood');
        $this->actingAs($this->auxiliary)->post(route('app.creche.journal.summary', $this->adam), ['date' => '2026-11-26', 'mood' => 'good'])->assertForbidden();
        $this->actingAs($this->manager)->post(route('app.creche.journal.summary', $this->adam), ['date' => '2026-11-26', 'mood' => 'good'])->assertSessionHasNoErrors();
    }

    // ------------------------------------------------------------------
    // Espace parent
    // ------------------------------------------------------------------

    public function test_parent_sees_the_day_of_their_child(): void
    {
        $this->fillTheDay();

        $this->actingAs($this->parent)->get(route('parent.child.details', $this->adam->id))->assertOk()->assertSee('La journée de Adam');

        $page = $this->actingAs($this->parent)->get(route('parent.creche.day', $this->adam->id));
        $page->assertOk()
            ->assertSee('Arrivée : ')->assertSee('07h42')->assertSee('Toujours à la crèche')
            ->assertSee('2 biberon(s) · 240 ml')
            ->assertSee('Purée de carottes · Tout mangé')
            ->assertSee('1h45')
            ->assertSee('2 change(s)')
            ->assertSee('Comptine · Une souris verte')
            ->assertSee('A dit « mama » pour la première fois !')
            // Pas de nom du personnel ; humeur pas encore publiée
            ->assertDontSee('Diallo')->assertDontSee('Message de l\'équipe', false);

        $this->actingAs($this->auxiliary)->post(route('app.creche.journal.summary', $this->adam), [
            'date' => '2026-11-27', 'mood' => 'very_good', 'message' => 'Très bonne journée !',
        ]);
        $this->actingAs($this->parent)->get(route('parent.creche.day', $this->adam->id))
            ->assertOk()->assertSee('😄 Très bien')->assertSee('Très bonne journée !');

        // Départ : affiché avec la personne venue le chercher
        $mother = new AuthorizedPickup(['first_name' => 'Fatou', 'last_name' => 'Koné', 'relationship' => 'Mère', 'status' => 'authorized']);
        $mother->school_id = $this->school->id;
        $mother->student_id = $this->adam->id;
        $mother->save();
        Carbon::setTestNow('2026-11-27 17:35:00');
        $this->actingAs($this->auxiliary)->post(route('app.creche.departure.store', $this->adam), ['pickup_id' => (string) $mother->id]);
        $this->actingAs($this->parent)->get(route('parent.creche.day', $this->adam->id))
            ->assertOk()->assertSee('17h35')->assertSee('avec Fatou Koné');
    }

    public function test_parent_only_sees_validated_incidents_when_the_school_allows_it(): void
    {
        $this->actingAs($this->auxiliary)->post(route('app.creche.arrival', $this->adam), ['time' => '08:00']);
        $this->actingAs($this->auxiliary)->post(route('app.creche.incidents.store'), [
            'student_id' => $this->adam->id, 'occurred_on' => '2026-11-27', 'occurred_at' => '10:20', 'type' => 'fall',
            'description' => 'Petite chute en courant.', 'action_taken' => 'Froid appliqué.',
        ])->assertSessionHasNoErrors();
        $incident = CareIncident::firstOrFail();

        // Déclaré mais pas validé : invisible
        $this->actingAs($this->parent)->get(route('parent.creche.day', $this->adam->id))->assertOk()->assertDontSee('Petite chute');

        $this->actingAs($this->manager)->post(route('app.creche.incidents.validate', $incident), ['comment' => 'Commentaire interne']);
        $this->actingAs($this->parent)->get(route('parent.creche.day', $this->adam->id))
            ->assertOk()->assertSee('Chute à 10h20')->assertSee('Petite chute en courant.')->assertSee('Froid appliqué.')
            ->assertDontSee('Commentaire interne');

        // L'école choisit de ne pas montrer les incidents
        $this->school->update(['settings' => ['creche' => ['incidents_parent_visibility' => 'none']]]);
        $this->actingAs($this->parent)->get(route('parent.creche.day', $this->adam->id))->assertOk()->assertDontSee('Petite chute');
    }

    public function test_parent_cannot_see_another_child_or_a_future_day(): void
    {
        $sarah = $this->makeChild('Sarah');

        $this->actingAs($this->parent)->get(route('parent.creche.day', $sarah->id))->assertNotFound();
        $this->actingAs($this->parent)->get(route('parent.creche.day', ['student' => $this->adam->id, 'date' => '2026-11-28']))->assertNotFound();

        // Le personnel n'utilise pas l'espace parent
        $this->actingAs($this->auxiliary)->get(route('parent.creche.day', $this->adam->id))->assertForbidden();
    }

    public function test_mobile_api_returns_the_day_for_own_children_only(): void
    {
        $this->fillTheDay();
        Sanctum::actingAs($this->parent);

        $this->getJson("/api/children/{$this->adam->id}/creche/day")
            ->assertOk()
            ->assertJsonPath('data.child.first_name', 'Adam')
            ->assertJsonPath('data.presence.arrived_at', '07:42')
            ->assertJsonPath('data.feeding.bottles_total_ml', 240)
            ->assertJsonPath('data.sleep.total_minutes', 105)
            ->assertJsonPath('data.hygiene.diapers', 2)
            ->assertJsonPath('data.activities.0.summary', 'Comptine · Une souris verte')
            ->assertJsonPath('data.published', false);

        $sarah = $this->makeChild('Sarah');
        $this->getJson("/api/children/{$sarah->id}/creche/day")->assertNotFound();
        $this->getJson("/api/children/{$this->adam->id}/creche/day?date=2026-11-28")->assertUnprocessable();
    }

    // ------------------------------------------------------------------
    // Notifications SMS
    // ------------------------------------------------------------------

    public function test_sms_notifications_follow_the_school_settings(): void
    {
        // SMS désactivés : rien ne part
        $this->actingAs($this->auxiliary)->post(route('app.creche.arrival', $this->adam), ['time' => '07:42']);
        $this->assertSame(0, SmsLog::count());

        // SMS activés, arrivée et départ cochés
        $this->enableSms(['arrival' => true, 'departure' => true, 'incident' => false, 'summary' => true]);
        $lina = $this->makeChild('Lina');
        $lina->parents()->attach($this->parent->id, ['school_id' => $this->school->id]);
        $this->actingAs($this->auxiliary)->post(route('app.creche.arrival', $lina), ['time' => '08:05']);

        $sms = SmsLog::where('trigger_type', 'creche_arrival')->firstOrFail();
        $this->assertSame('0707070707', $sms->recipient_phone);
        $this->assertSame('Lina est bien arrivé(e) à la crèche à 08h05. Les Mirabelles', $sms->message);
        $this->assertStringNotContainsString('Koné', $sms->message, 'Règle 10 : prénom seulement.');
        // Passerelle non configurée : envoi tenté après la réponse et noté en échec
        $this->assertSame('failed', $sms->fresh()->status);

        // Départ : prénom de l'enfant et nom de la personne qui l'a récupéré
        $mother = new AuthorizedPickup(['first_name' => 'Fatou', 'last_name' => 'Koné', 'relationship' => 'Mère', 'status' => 'authorized']);
        $mother->school_id = $this->school->id;
        $mother->student_id = $lina->id;
        $mother->save();
        Carbon::setTestNow('2026-11-27 17:35:00');
        $this->actingAs($this->auxiliary)->post(route('app.creche.departure.store', $lina), ['pickup_id' => (string) $mother->id]);
        $this->assertSame('Lina a quitté la crèche à 17h35 avec Fatou Koné. Les Mirabelles', SmsLog::where('trigger_type', 'creche_departure')->value('message'));

        // Carnet du jour : seulement à la première publication
        $this->actingAs($this->auxiliary)->post(route('app.creche.journal.summary', $lina), ['date' => '2026-11-27', 'mood' => 'good']);
        $this->actingAs($this->auxiliary)->post(route('app.creche.journal.summary', $lina), ['date' => '2026-11-27', 'mood' => 'very_good']);
        $this->assertSame(1, SmsLog::where('trigger_type', 'creche_summary')->count());

        // Incident non coché : pas de SMS à la validation
        $this->actingAs($this->auxiliary)->post(route('app.creche.incidents.store'), [
            'student_id' => $lina->id, 'occurred_on' => '2026-11-27', 'occurred_at' => '10:00', 'type' => 'fall', 'description' => 'Chute.',
        ]);
        $this->actingAs($this->manager)->post(route('app.creche.incidents.validate', CareIncident::firstOrFail()));
        $this->assertSame(0, SmsLog::where('trigger_type', 'creche_incident')->count());
    }

    public function test_incident_sms_is_sent_on_validation_not_on_declaration(): void
    {
        $this->enableSms(['incident' => true]);

        $this->actingAs($this->auxiliary)->post(route('app.creche.incidents.store'), [
            'student_id' => $this->adam->id, 'occurred_on' => '2026-11-27', 'occurred_at' => '10:00', 'type' => 'injury', 'description' => 'Griffure.',
        ]);
        $this->assertSame(0, SmsLog::count());

        $this->actingAs($this->manager)->post(route('app.creche.incidents.validate', CareIncident::firstOrFail()));
        $this->assertSame(
            'Une observation concernant votre enfant Adam nécessite votre attention. Consultez votre espace parent ou contactez la crèche. Les Mirabelles',
            SmsLog::where('trigger_type', 'creche_incident')->value('message')
        );
    }

    public function test_settings_page_saves_notification_choices(): void
    {
        $this->actingAs($this->manager)->get(route('app.creche.settings.edit'))->assertOk()->assertSee('Les SMS sont désactivés');

        $this->actingAs($this->manager)->put(route('app.creche.settings.update'), [
            'levels' => 'Bébés (0-1 an)', 'opening_time' => '07:00', 'closing_time' => '18:00',
            'notifications_form' => '1', 'notify_departure' => '1',
        ])->assertSessionHasNoErrors();

        $school = $this->school->fresh();
        $this->assertTrue($school->setting('creche.notify_departure'));
        $this->assertFalse($school->setting('creche.notify_incident'));
        $this->assertFalse($school->setting('creche.notify_arrival'));
    }
}
