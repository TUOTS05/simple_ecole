<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AuthorizedPickup;
use App\Models\CareStaffAssignment;
use App\Models\ChildCareProfile;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Module Crèche — lot 1 : dossier crèche (profil de prise en charge), personnes autorisées,
 * inscription en crèche et stockage privé des photos et documents.
 */
class CrecheChildRecordTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private SchoolClass $babies;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->school = School::create(['name' => 'Les Mirabelles', 'slug' => 'les-mirabelles', 'status' => 'active', 'structures' => ['creche', 'maternelle']]);
        SchoolYear::create(['school_id' => $this->school->id, 'name' => '2026-2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-01', 'is_active' => true]);
        $this->admin = $this->makeUser('school_admin', 'directeur@example.test');
        $this->babies = $this->makeGroup('Bébés');
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
        return SchoolClass::create([
            'school_id' => ($school ?? $this->school)->id, 'name' => $name, 'level' => 'Bébés (0-1 an)', 'cycle' => 'creche', 'capacity' => 10,
        ]);
    }

    private function makeChild(SchoolClass $group, string $firstName = 'Adam', array $attributes = []): Student
    {
        $student = new Student(array_merge(['first_name' => $firstName, 'last_name' => 'Koné', 'birth_date' => '2025-06-01', 'gender' => 'M'], $attributes));
        $student->school_id = $group->school_id;
        $student->save();
        $student->classes()->attach($group->id);

        return $student;
    }

    private function pickupPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Mariam', 'last_name' => 'Koné', 'relationship' => 'Tante',
            'phone' => '0707070707', 'status' => 'authorized',
        ], $overrides);
    }

    // ------------------------------------------------------------------
    // Inscription en crèche et stockage privé
    // ------------------------------------------------------------------

    public function test_enrolling_a_child_in_a_creche_group_opens_its_creche_record(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->admin)->post(route('app.enrollments.store'), [
            'first_name' => 'Adam', 'last_name' => 'Koné', 'gender' => 'M', 'birth_date' => '2025-06-01',
            'class_id' => $this->babies->id, 'status' => 'active', 'admission_date' => '2026-09-02', 'receipt_number' => 'R-001',
            'guardian_type' => 'mother', 'guardian_first_name' => 'Fatou', 'guardian_last_name' => 'Koné',
            'guardian_phone' => '0101010101', 'guardian_email' => 'fatou@example.test',
            'student_photo' => UploadedFile::fake()->image('adam.jpg'),
        ]);

        $student = Student::where('first_name', 'Adam')->firstOrFail();
        $response->assertRedirect(route('app.creche.children.show', $student));
        $response->assertSessionHas('success', fn ($message) => str_contains($message, 'personnes autorisées'));

        // Un seul enfant, avec son dossier crèche (date d'entrée = date d'admission)
        $this->assertSame(1, Student::count());
        $this->assertSame('2026-09-02', $student->careProfile->entry_date->toDateString());

        // La photo est enregistrée (elle était perdue avant) et seulement sur le disque privé
        $this->assertNotNull($student->photo);
        Storage::disk('local')->assertExists($student->photo);
        Storage::disk('public')->assertMissing($student->photo);

        $this->actingAs($this->admin)->get(route('app.students.photo', $student))->assertOk();
        $this->actingAs($this->admin)->get(route('app.creche.children.show', $student))
            ->assertOk()->assertSee('Aucune personne enregistrée');
    }

    public function test_school_classes_do_not_get_a_creche_record(): void
    {
        $maternelle = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'PS A', 'level' => 'PS', 'cycle' => 'maternelle']);
        $student = new Student(['first_name' => 'Sarah', 'last_name' => 'Bamba', 'birth_date' => '2022-01-01', 'gender' => 'F']);
        $student->school_id = $this->school->id;
        $student->save();
        $student->classes()->attach($maternelle->id);

        $this->actingAs($this->admin)->get(route('app.creche.children.show', $student))->assertNotFound();
        $this->assertSame(0, ChildCareProfile::count());
    }

    public function test_legacy_public_files_are_still_served_then_moved_by_the_command(): void
    {
        $child = $this->makeChild($this->babies, 'Adam', ['photo' => 'students/photos/old.jpg', 'documents' => ['doc_1' => 'students/documents/acte.pdf']]);
        Storage::disk('public')->put('students/photos/old.jpg', 'jpg');
        Storage::disk('public')->put('students/documents/acte.pdf', 'pdf');
        Storage::disk('public')->put('students/photos/orpheline.jpg', 'jpg'); // envoyée avant la correction, jamais liée

        $this->actingAs($this->admin)->get(route('app.students.photo', $child))->assertOk();

        $this->artisan('files:secure-students')->expectsOutputToContain('3 fichier(s)')->assertSuccessful();

        foreach (['students/photos/old.jpg', 'students/documents/acte.pdf', 'students/photos/orpheline.jpg'] as $path) {
            Storage::disk('public')->assertMissing($path);
            Storage::disk('local')->assertExists($path);
        }
        $this->actingAs($this->admin)->get(route('app.students.photo', $child))->assertOk();
        $this->actingAs($this->admin)->get(route('app.students.document', [$child, 1]))->assertOk();
        $this->actingAs($this->admin)->get(route('app.students.document', [$child, 2]))->assertNotFound();
    }

    // ------------------------------------------------------------------
    // Profil de prise en charge
    // ------------------------------------------------------------------

    public function test_director_fills_the_care_profile_and_it_is_audited(): void
    {
        $child = $this->makeChild($this->babies);
        $auxiliary = $this->makeUser('care_staff', 'aux@example.test');

        $this->actingAs($this->admin)->get(route('app.creche.children.edit', $child))->assertOk()->assertSee('Personne référente');

        $this->actingAs($this->admin)->put(route('app.creche.children.update', $child), [
            'entry_date' => '2026-09-02', 'care_arrangement' => 'full_time', 'referent_user_id' => $auxiliary->id,
            'milk_type' => 'Lait 2e âge', 'formula_fed' => '1', 'usual_quantity' => '150 ml',
            'allergies' => 'Arachide', 'forbidden_foods' => 'Porc',
            'comforter' => 'Lapin bleu', 'pacifier' => '1', 'particularities' => 'S\'endort avec une berceuse.',
        ])->assertRedirect(route('app.creche.children.show', $child))->assertSessionHasNoErrors();

        $profile = $child->fresh()->careProfile;
        $this->assertSame('Lait 2e âge', $profile->milk_type);
        $this->assertTrue($profile->formula_fed);
        $this->assertFalse($profile->breastfed);
        $this->assertTrue($profile->pacifier);
        $this->assertSame($auxiliary->id, $profile->referent_user_id);
        $this->assertSame($this->admin->id, $profile->updated_by);

        $this->actingAs($this->admin)->get(route('app.creche.children.show', $child))
            ->assertOk()->assertSee('Points de vigilance')->assertSee('Arachide')->assertSee('Lapin bleu')->assertSee('Temps plein');

        $log = ActivityLog::where('action', 'creche.profile.updated')->firstOrFail();
        $this->assertSame($this->school->id, $log->school_id);
        $this->assertSame('Student', $log->subject_type);
        $this->assertSame($child->id, $log->subject_id);
        $this->assertContains('allergies', $log->meta['fields']);

        // Un parent ou un comptable ne peut pas être personne référente
        $accountant = $this->makeUser('accountant', 'compta@example.test');
        $this->actingAs($this->admin)->put(route('app.creche.children.update', $child), ['referent_user_id' => $accountant->id])
            ->assertSessionHasErrors('referent_user_id');
    }

    // ------------------------------------------------------------------
    // Personnes autorisées
    // ------------------------------------------------------------------

    public function test_authorized_pickups_are_added_updated_and_removed_with_an_audit_trail(): void
    {
        $child = $this->makeChild($this->babies);

        $this->actingAs($this->admin)->post(route('app.creche.pickups.store', $child), $this->pickupPayload([
            'photo' => UploadedFile::fake()->image('mariam.png'),
        ]))->assertRedirect(route('app.creche.children.show', $child))->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->post(route('app.creche.pickups.store', $child), $this->pickupPayload([
            'first_name' => 'Ibrahim', 'last_name' => 'Traoré', 'relationship' => 'Voisin(e)', 'status' => 'forbidden', 'comment' => 'Signalé par la mère',
        ]))->assertSessionHasNoErrors();

        $aunt = AuthorizedPickup::where('first_name', 'Mariam')->firstOrFail();
        Storage::disk('local')->assertExists($aunt->photo);
        $this->actingAs($this->admin)->get(route('app.creche.pickups.photo', $aunt))->assertOk();

        $this->actingAs($this->admin)->get(route('app.creche.children.show', $child))
            ->assertOk()->assertSee('Mariam Koné')->assertSee('Autorisée')->assertSee('Ibrahim Traoré')->assertSee('NON AUTORISÉE');

        // Modification : l'autorisation devient limitée dans le temps
        $this->actingAs($this->admin)->put(route('app.creche.pickups.update', $aunt), $this->pickupPayload([
            'starts_on' => '2026-01-01', 'ends_on' => '2026-06-30', 'remove_photo' => '1',
        ]))->assertSessionHasNoErrors();
        $aunt->refresh();
        $this->assertNull($aunt->photo);
        $this->assertSame('Expirée', $aunt->stateLabel(now()->setDate(2026, 11, 27)));
        $this->assertFalse($aunt->isAuthorizedOn(now()->setDate(2026, 11, 27)));
        $this->assertTrue($aunt->isAuthorizedOn(now()->setDate(2026, 3, 1)));

        // Retrait : suppression douce, toujours traçable
        $this->actingAs($this->admin)->delete(route('app.creche.pickups.destroy', $aunt))->assertRedirect();
        $this->assertSoftDeleted($aunt);

        $this->assertSame(
            ['creche.pickup.created', 'creche.pickup.created', 'creche.pickup.updated', 'creche.pickup.deleted'],
            ActivityLog::orderBy('id')->pluck('action')->all()
        );
        $update = ActivityLog::where('action', 'creche.pickup.updated')->firstOrFail();
        $this->assertNull($update->meta['before']['ends_on']);
        $this->assertSame('2026-06-30', $update->meta['after']['ends_on']);
    }

    public function test_pickup_form_is_validated(): void
    {
        $child = $this->makeChild($this->babies);

        $this->actingAs($this->admin)->post(route('app.creche.pickups.store', $child), $this->pickupPayload([
            'starts_on' => '2026-06-30', 'ends_on' => '2026-01-01',
            'photo' => UploadedFile::fake()->create('virus.php', 10, 'application/x-php'),
            'phone' => 'appelle-moi', 'status' => 'peut-être',
        ]))->assertSessionHasErrors(['ends_on', 'photo', 'phone', 'status']);

        $this->assertSame(0, AuthorizedPickup::count());
    }

    // ------------------------------------------------------------------
    // Droits : auxiliaire et cloisonnement
    // ------------------------------------------------------------------

    public function test_auxiliary_reads_only_the_records_of_her_groups(): void
    {
        $toddlers = $this->makeGroup('Petits');
        $adam = $this->makeChild($this->babies, 'Adam', ['photo' => 'students/photos/adam.jpg', 'documents' => ['doc_1' => 'students/documents/adam.pdf']]);
        $sarah = $this->makeChild($toddlers, 'Sarah', ['photo' => 'students/photos/sarah.jpg']);
        Storage::disk('local')->put('students/photos/adam.jpg', 'jpg');
        Storage::disk('local')->put('students/photos/sarah.jpg', 'jpg');
        Storage::disk('local')->put('students/documents/adam.pdf', 'pdf');

        $auxiliary = $this->makeUser('care_staff', 'aux@example.test');
        CareStaffAssignment::create(['school_id' => $this->school->id, 'school_class_id' => $this->babies->id, 'user_id' => $auxiliary->id]);

        $this->actingAs($auxiliary)->get(route('app.creche.children.index'))
            ->assertOk()->assertSee('Adam')->assertDontSee('Sarah');
        $this->actingAs($auxiliary)->get(route('app.creche.children.index', ['group' => $toddlers->id]))->assertForbidden();

        // Son groupe : consultation seule, photo visible (utile au retrait), documents non
        $this->actingAs($auxiliary)->get(route('app.creche.children.show', $adam))
            ->assertOk()->assertDontSee('Modifier le dossier')->assertDontSee('+ Ajouter une personne');
        $this->actingAs($auxiliary)->get(route('app.students.photo', $adam))->assertOk();
        $this->actingAs($auxiliary)->get(route('app.students.document', [$adam, 1]))->assertForbidden();
        $this->actingAs($auxiliary)->get(route('app.creche.children.edit', $adam))->assertForbidden();
        $this->actingAs($auxiliary)->put(route('app.creche.children.update', $adam), ['allergies' => 'Aucune'])->assertForbidden();
        $this->actingAs($auxiliary)->post(route('app.creche.pickups.store', $adam), $this->pickupPayload())->assertForbidden();

        // Un enfant d'un autre groupe : rien, même en changeant l'identifiant dans l'URL
        $this->actingAs($auxiliary)->get(route('app.creche.children.show', $sarah))->assertForbidden();
        $this->actingAs($auxiliary)->get(route('app.students.photo', $sarah))->assertForbidden();

        $this->assertSame(0, AuthorizedPickup::count());
        $this->assertNull($adam->fresh()->careProfile);
    }

    public function test_records_and_files_stay_inside_their_school(): void
    {
        $other = School::create(['name' => 'Autre crèche', 'slug' => 'autre', 'status' => 'active', 'structures' => ['creche']]);
        $otherDirector = $this->makeUser('school_admin', 'dir2@example.test', $other);

        $child = $this->makeChild($this->babies, 'Adam', ['photo' => 'students/photos/adam.jpg']);
        Storage::disk('local')->put('students/photos/adam.jpg', 'jpg');
        $pickup = new AuthorizedPickup($this->pickupPayload());
        $pickup->school_id = $this->school->id;
        $pickup->student_id = $child->id;
        $pickup->save();

        $this->actingAs($otherDirector)->get(route('app.creche.children.show', $child))->assertNotFound();
        $this->actingAs($otherDirector)->get(route('app.students.photo', $child))->assertNotFound();
        $this->actingAs($otherDirector)->get(route('app.creche.pickups.photo', $pickup))->assertNotFound();
        $this->actingAs($otherDirector)->put(route('app.creche.pickups.update', $pickup), $this->pickupPayload(['status' => 'forbidden']))->assertNotFound();
        $this->actingAs($otherDirector)->delete(route('app.creche.pickups.destroy', $pickup))->assertNotFound();

        $this->assertSame('authorized', $pickup->fresh()->status);
    }
}
