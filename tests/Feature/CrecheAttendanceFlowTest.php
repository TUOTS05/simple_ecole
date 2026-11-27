<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AuthorizedPickup;
use App\Models\CareStaffAssignment;
use App\Models\CrecheAttendance;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Module Crèche — lot 2 : arrivées, absences et retrait sécurisé de l'enfant
 * (règles 1 à 3 du cahier des charges, retard de récupération, journal).
 */
class CrecheAttendanceFlowTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $auxiliary;

    private SchoolClass $babies;

    private Student $adam;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-27 07:50:00');

        $this->school = School::create(['name' => 'Les Mirabelles', 'slug' => 'les-mirabelles', 'status' => 'active', 'structures' => ['creche']]);
        SchoolYear::create(['school_id' => $this->school->id, 'name' => '2026-2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-01', 'is_active' => true]);
        $this->babies = $this->makeGroup('Bébés');
        $this->auxiliary = $this->makeUser('care_staff', 'aux@example.test');
        CareStaffAssignment::create(['school_id' => $this->school->id, 'school_class_id' => $this->babies->id, 'user_id' => $this->auxiliary->id]);
        $this->adam = $this->makeChild($this->babies, 'Adam');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeUser(string $role, string $email): User
    {
        $user = new User(['first_name' => 'Awa', 'last_name' => $role, 'email' => $email, 'password' => bcrypt('password')]);
        $user->role = $role;
        $user->school_id = $this->school->id;
        $user->save();

        return $user;
    }

    private function makeGroup(string $name, array $attributes = []): SchoolClass
    {
        return SchoolClass::create(array_merge(['school_id' => $this->school->id, 'name' => $name, 'level' => 'Bébés (0-1 an)', 'cycle' => 'creche'], $attributes));
    }

    private function makeChild(SchoolClass $group, string $firstName): Student
    {
        $student = new Student(['first_name' => $firstName, 'last_name' => 'Koné', 'birth_date' => '2025-06-01', 'gender' => 'M', 'matricule' => 'CR-'.$firstName]);
        $student->school_id = $this->school->id;
        $student->save();
        $student->classes()->attach($group->id);

        return $student;
    }

    private function makePickup(Student $child, string $firstName, array $attributes = []): AuthorizedPickup
    {
        $pickup = new AuthorizedPickup(array_merge(['first_name' => $firstName, 'last_name' => 'Koné', 'relationship' => 'Mère', 'status' => 'authorized'], $attributes));
        $pickup->school_id = $this->school->id;
        $pickup->student_id = $child->id;
        $pickup->save();

        return $pickup;
    }

    private function attendance(Student $child): ?CrecheAttendance
    {
        return CrecheAttendance::where('student_id', $child->id)->first();
    }

    // ------------------------------------------------------------------
    // Arrivées et absences
    // ------------------------------------------------------------------

    public function test_arrival_is_recorded_once_and_absence_cannot_follow_it(): void
    {
        $this->actingAs($this->auxiliary)->post(route('app.creche.arrival', $this->adam), [
            'time' => '07:42', 'dropped_off_by' => 'Fatou Koné (Mère)', 'is_late' => '1', 'notes' => 'A mal dormi',
        ])->assertRedirect(route('app.creche.today', ['group' => $this->babies->id]))->assertSessionHasNoErrors();

        $attendance = $this->attendance($this->adam);
        $this->assertSame('present', $attendance->status);
        $this->assertSame('07:42', $attendance->checked_in_at->format('H:i'));
        $this->assertSame($this->auxiliary->id, $attendance->checked_in_by);
        $this->assertTrue($attendance->is_late);
        $this->assertSame('Fatou Koné (Mère)', $attendance->dropped_off_by);

        // Règle 1 : une seule présence active
        $this->actingAs($this->auxiliary)->post(route('app.creche.arrival', $this->adam))->assertSessionHasErrors('attendance');
        $this->actingAs($this->auxiliary)->post(route('app.creche.absence', $this->adam), ['justified' => '1'])->assertSessionHasErrors('attendance');
        $this->assertSame(1, CrecheAttendance::count());
        $this->assertSame('present', $this->attendance($this->adam)->status);

        $this->assertSame(1, ActivityLog::where('action', 'creche.arrival')->count());
    }

    public function test_arrival_time_in_the_future_is_brought_back_to_now(): void
    {
        $this->actingAs($this->auxiliary)->post(route('app.creche.arrival', $this->adam), ['time' => '11:00']);

        $this->assertSame('07:50', $this->attendance($this->adam)->checked_in_at->format('H:i'));
    }

    public function test_justified_absence_then_late_arrival(): void
    {
        $this->actingAs($this->auxiliary)->post(route('app.creche.absence', $this->adam), ['justified' => '1', 'reason' => 'Rendez-vous médical'])
            ->assertSessionHasNoErrors();
        $this->assertSame('absent_justified', $this->attendance($this->adam)->status);

        // Pas de départ pour un enfant absent
        $mother = $this->makePickup($this->adam, 'Fatou');
        $this->actingAs($this->auxiliary)->post(route('app.creche.departure.store', $this->adam), ['pickup_id' => (string) $mother->id])
            ->assertSessionHasErrors('departure');

        // L'enfant arrive finalement plus tard
        Carbon::setTestNow('2026-11-27 10:15:00');
        $this->actingAs($this->auxiliary)->post(route('app.creche.arrival', $this->adam))->assertSessionHasNoErrors();
        $attendance = $this->attendance($this->adam);
        $this->assertSame('present', $attendance->status);
        $this->assertNull($attendance->absence_reason);
    }

    // ------------------------------------------------------------------
    // Retrait sécurisé
    // ------------------------------------------------------------------

    public function test_authorized_person_takes_the_child_and_a_second_departure_is_refused(): void
    {
        $mother = $this->makePickup($this->adam, 'Fatou', ['phone' => '0707070707']);
        $this->actingAs($this->auxiliary)->post(route('app.creche.arrival', $this->adam), ['time' => '07:42']);

        Carbon::setTestNow('2026-11-27 17:35:00');
        $this->actingAs($this->auxiliary)->get(route('app.creche.departure.show', $this->adam))
            ->assertOk()->assertSee('Qui vient chercher Adam')->assertSee('Fatou Koné');

        $this->actingAs($this->auxiliary)->post(route('app.creche.departure.store', $this->adam), [
            'pickup_id' => (string) $mother->id, 'identity_checked' => '1',
        ])->assertRedirect(route('app.creche.departure.show', $this->adam));

        $attendance = $this->attendance($this->adam);
        $this->assertSame('left', $attendance->status);
        $this->assertSame('17:35', $attendance->checked_out_at->format('H:i'));
        $this->assertSame($mother->id, $attendance->pickup_person_id);
        $this->assertSame('Fatou Koné (Mère)', $attendance->picked_up_by);
        $this->assertSame('identity', $attendance->verification_mode);
        $this->assertSame($this->auxiliary->id, $attendance->checked_out_by);
        $this->assertFalse($attendance->late_pickup);

        $this->actingAs($this->auxiliary)->get(route('app.creche.departure.show', $this->adam))->assertSee('DÉPART AUTORISÉ');

        $log = ActivityLog::where('action', 'creche.departure')->firstOrFail();
        $this->assertSame('Student', $log->subject_type);
        $this->assertSame($this->adam->id, $log->subject_id);
        $this->assertSame('Fatou Koné', $log->meta['person']);

        // Règle 2 : pas de second départ sans nouvelle arrivée
        $this->actingAs($this->auxiliary)->post(route('app.creche.departure.store', $this->adam), ['pickup_id' => (string) $mother->id])
            ->assertSessionHasErrors('departure');
        $this->assertSame(1, ActivityLog::where('action', 'creche.departure')->count());

        // Retour dans la journée puis nouveau départ : accepté
        $this->actingAs($this->auxiliary)->post(route('app.creche.arrival', $this->adam))->assertSessionHasNoErrors();
        Carbon::setTestNow('2026-11-27 17:50:00');
        $this->actingAs($this->auxiliary)->post(route('app.creche.departure.store', $this->adam), ['pickup_id' => (string) $mother->id]);
        $this->assertSame('17:50', $this->attendance($this->adam)->checked_out_at->format('H:i'));
        $this->assertSame('list', $this->attendance($this->adam)->verification_mode);
    }

    public function test_departure_is_blocked_for_anyone_not_currently_authorized(): void
    {
        $this->actingAs($this->auxiliary)->post(route('app.creche.arrival', $this->adam));
        $sarah = $this->makeChild($this->babies, 'Sarah');

        $cases = [
            'interdite' => $this->makePickup($this->adam, 'Ibrahim', ['relationship' => 'Voisin(e)', 'status' => 'forbidden']),
            'expirée' => $this->makePickup($this->adam, 'Mariam', ['relationship' => 'Tante', 'ends_on' => '2026-11-26']),
            'pas encore valable' => $this->makePickup($this->adam, 'Awa', ['relationship' => 'Nounou', 'starts_on' => '2026-12-01']),
            'retirée' => tap($this->makePickup($this->adam, 'Koffi', ['relationship' => 'Oncle']))->delete(),
            "d'un autre enfant" => $this->makePickup($sarah, 'Aya', ['relationship' => 'Mère']),
        ];

        foreach ($cases as $label => $pickup) {
            $this->actingAs($this->auxiliary)->post(route('app.creche.departure.store', $this->adam), ['pickup_id' => (string) $pickup->id])
                ->assertRedirect(route('app.creche.departure.show', $this->adam));
            $this->assertSame('present', $this->attendance($this->adam)->status, "Personne {$label} : l'enfant ne doit pas partir.");
        }

        // Personne absente de la liste
        $this->actingAs($this->auxiliary)->post(route('app.creche.departure.store', $this->adam), ['pickup_id' => 'other', 'other_name' => 'Un inconnu'])
            ->assertRedirect();
        $this->actingAs($this->auxiliary)->post(route('app.creche.departure.store', $this->adam), ['pickup_id' => 'other'])
            ->assertSessionHasErrors('other_name');

        $this->actingAs($this->auxiliary)->get(route('app.creche.departure.show', $this->adam))->assertOk();
        $this->assertSame('present', $this->attendance($this->adam)->status);
        $this->assertNull($this->attendance($this->adam)->checked_out_at);

        $blocked = ActivityLog::where('action', 'creche.departure.blocked')->orderBy('id')->get();
        $this->assertCount(6, $blocked);
        $this->assertStringContainsString('INTERDITE', $blocked[0]->meta['reason']);
        $this->assertStringContainsString('expirée', $blocked[1]->meta['reason']);
        $this->assertSame('Un inconnu', $blocked[5]->meta['person']);
        $this->assertSame(0, ActivityLog::where('action', 'creche.departure')->count());
    }

    public function test_blocked_departure_shows_the_alert(): void
    {
        $neighbour = $this->makePickup($this->adam, 'Ibrahim', ['relationship' => 'Voisin(e)', 'status' => 'forbidden']);
        $this->adam->update(['mother_phone' => '0101010101']);
        $this->actingAs($this->auxiliary)->post(route('app.creche.arrival', $this->adam));

        $this->actingAs($this->auxiliary)
            ->followingRedirects()
            ->post(route('app.creche.departure.store', $this->adam), ['pickup_id' => (string) $neighbour->id])
            ->assertOk()->assertSee('DÉPART BLOQUÉ')->assertSee('INTERDITE')->assertSee('0101010101');
    }

    public function test_pickup_after_closing_time_is_flagged(): void
    {
        $mother = $this->makePickup($this->adam, 'Fatou');
        $this->actingAs($this->auxiliary)->post(route('app.creche.arrival', $this->adam));

        // Fermeture de la crèche par défaut : 18:00
        Carbon::setTestNow('2026-11-27 18:20:00');
        $this->actingAs($this->auxiliary)->post(route('app.creche.departure.store', $this->adam), ['pickup_id' => (string) $mother->id]);
        $this->assertTrue($this->attendance($this->adam)->late_pickup);
        $this->actingAs($this->auxiliary)->get(route('app.creche.departure.show', $this->adam))->assertSee("après l'heure de fermeture", false);

        // Un groupe qui ferme plus tôt a ses propres horaires
        $early = $this->makeGroup('Mercredi', ['closing_time' => '12:30']);
        $sarah = $this->makeChild($early, 'Sarah');
        CareStaffAssignment::create(['school_id' => $this->school->id, 'school_class_id' => $early->id, 'user_id' => $this->auxiliary->id]);
        $aunt = $this->makePickup($sarah, 'Mariam', ['relationship' => 'Tante']);
        Carbon::setTestNow('2026-11-27 12:00:00');
        $this->actingAs($this->auxiliary)->post(route('app.creche.arrival', $sarah), ['time' => '08:00']);
        Carbon::setTestNow('2026-11-27 12:45:00');
        $this->actingAs($this->auxiliary)->post(route('app.creche.departure.store', $sarah), ['pickup_id' => (string) $aunt->id]);
        $this->assertTrue($this->attendance($sarah)->late_pickup);
    }

    // ------------------------------------------------------------------
    // Écrans et droits
    // ------------------------------------------------------------------

    public function test_today_screen_and_departure_search(): void
    {
        $sarah = $this->makeChild($this->babies, 'Sarah');
        $lina = $this->makeChild($this->babies, 'Lina');
        $this->actingAs($this->auxiliary)->post(route('app.creche.arrival', $this->adam));
        $this->actingAs($this->auxiliary)->post(route('app.creche.absence', $lina));

        $this->actingAs($this->auxiliary)->get(route('app.creche.today'))
            ->assertOk()->assertSee('Adam Koné')->assertSee('Arrivé(e) à 07:50')->assertSee('Attendu(e)')->assertSee('Absent');

        // Par défaut, la recherche de départ liste les enfants encore présents
        $this->actingAs($this->auxiliary)->get(route('app.creche.departure.index'))
            ->assertOk()->assertSee('Adam')->assertDontSee('Sarah')->assertDontSee('Lina');
        $this->actingAs($this->auxiliary)->get(route('app.creche.departure.index', ['search' => 'Sara']))
            ->assertOk()->assertSee('Sarah');
        // Matricule exact (scan) : fiche de départ directement
        $this->actingAs($this->auxiliary)->get(route('app.creche.departure.index', ['search' => 'CR-Adam']))
            ->assertRedirect(route('app.creche.departure.show', $this->adam));
    }

    public function test_staff_only_handles_children_of_their_groups_and_reception_sees_all(): void
    {
        $toddlers = $this->makeGroup('Petits');
        $sarah = $this->makeChild($toddlers, 'Sarah');

        $this->actingAs($this->auxiliary)->get(route('app.creche.today', ['group' => $toddlers->id]))->assertForbidden();
        $this->actingAs($this->auxiliary)->get(route('app.creche.departure.show', $sarah))->assertForbidden();
        $this->actingAs($this->auxiliary)->post(route('app.creche.departure.store', $sarah), ['pickup_id' => 'other', 'other_name' => 'X'])->assertForbidden();
        $this->actingAs($this->auxiliary)->get(route('app.creche.departure.index', ['search' => 'Sarah']))->assertOk()->assertSee('Aucun enfant trouvé dans vos groupes');

        // L'accueil reçoit les familles de tous les groupes, sans affectation
        $reception = $this->makeUser('reception', 'accueil@example.test');
        $this->actingAs($reception)->post(route('app.creche.arrival', $sarah))->assertSessionHasNoErrors();
        $this->actingAs($reception)->get(route('app.creche.today', ['group' => $toddlers->id]))->assertOk()->assertSee('Sarah');

        // Un comptable n'a pas accès au pointage
        $accountant = $this->makeUser('accountant', 'compta@example.test');
        $this->actingAs($accountant)->get(route('app.creche.today'))->assertForbidden();
        $this->actingAs($accountant)->post(route('app.creche.arrival', $this->adam))->assertForbidden();
    }

    public function test_manager_correction_of_a_departure_is_marked_unverified(): void
    {
        $manager = $this->makeUser('care_manager', 'resp@example.test');
        $this->actingAs($this->auxiliary)->post(route('app.creche.arrival', $this->adam), ['time' => '07:42']);
        Carbon::setTestNow('2026-11-27 19:00:00');

        $this->actingAs($manager)->post(route('app.creche-attendances.store'), [
            'class_id' => $this->babies->id, 'date' => '2026-11-27',
            'records' => [$this->adam->id => ['status' => 'present', 'checked_in_at' => '07:42', 'checked_out_at' => '18:10', 'picked_up_by' => 'Le père']],
        ])->assertSessionHasNoErrors();

        $attendance = $this->attendance($this->adam);
        $this->assertSame('left', $attendance->status);
        $this->assertSame('correction', $attendance->verification_mode);
        $this->assertNull($attendance->pickup_person_id);
        $this->assertTrue($attendance->late_pickup);

        $this->actingAs($manager)->get(route('app.creche-attendances.index', ['class_id' => $this->babies->id, 'date' => '2026-11-27']))
            ->assertOk()->assertSee('Départ non vérifié (correction)');

        $this->actingAs($manager)->post(route('app.creche-attendances.store'), [
            'class_id' => $this->babies->id, 'date' => '2026-11-28', 'records' => [$this->adam->id => ['status' => 'absent']],
        ])->assertSessionHasErrors('date');
    }
}
