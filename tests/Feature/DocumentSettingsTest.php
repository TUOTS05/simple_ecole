<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Support\DocumentSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(School $school, string $role): User
    {
        $user = new User([
            'first_name' => 'Test',
            'last_name' => $role,
            'email' => $role.'-'.$school->slug.'@example.test',
            'password' => bcrypt('password'),
        ]);
        $user->role = $role;
        $user->school_id = $school->id;
        $user->save();

        return $user;
    }

    private function validPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'header' => ['lines' => "RÉPUBLIQUE DE CÔTE D'IVOIRE", 'motto' => 'Union - Discipline - Travail', 'city' => 'Bouaké'],
            'branding' => ['primary_color' => '#aa0000', 'accent_color' => '#00aa00', 'signatory_title' => 'Le Directeur', 'signatory_name' => 'M. Test'],
            'report_card' => [
                'title_monthly' => 'Relevé mensuel', 'title_quarterly' => 'Bulletin du trimestre',
                'teacher_box_title' => 'Avis du maître', 'footer_note' => '',
                'show_rank' => '0', 'show_class_size' => '1', 'show_council_mention' => '1', 'show_stamp' => '1',
                'scale' => [
                    ['min' => '10', 'label' => 'Acquis'],
                    ['min' => '15', 'label' => 'Très bien acquis'],
                    ['min' => '', 'label' => ''],
                    ['min' => '0', 'label' => 'Non acquis'],
                ],
            ],
            'student_card' => ['title' => 'Carte élève', 'footer_note' => '', 'show_birth_date' => '1', 'show_gender' => '0'],
            'receipt' => ['title' => 'Quittance', 'currency' => 'XOF', 'signature_label' => 'Visa', 'footer_text' => 'Merci', 'show_pending_installments' => '1', 'show_stamp' => '0'],
        ], $overrides);
    }

    public function test_school_without_settings_gets_historical_defaults(): void
    {
        $school = School::create(['name' => 'École A', 'slug' => 'a', 'status' => 'active']);

        $settings = DocumentSettings::for($school);

        $this->assertSame('Bulletin trimestriel', $settings['report_card']['title_quarterly']);
        $this->assertSame('Très Bien', DocumentSettings::appreciation($settings['report_card']['scale'], 16.5));
        $this->assertSame('Insuffisant', DocumentSettings::appreciation($settings['report_card']['scale'], 3));
    }

    public function test_admin_saves_settings_and_scale_is_cleaned_and_sorted(): void
    {
        $school = School::create(['name' => 'École A', 'slug' => 'a', 'status' => 'active']);
        $admin = $this->makeUser($school, 'school_admin');

        $this->actingAs($admin)
            ->put(route('app.settings.documents.update'), $this->validPayload())
            ->assertRedirect(route('app.settings.documents'))
            ->assertSessionHasNoErrors();

        $settings = DocumentSettings::for($school->fresh());

        $this->assertSame('Quittance', $settings['receipt']['title']);
        $this->assertFalse($settings['report_card']['show_rank']);
        $this->assertFalse($settings['student_card']['show_gender']);
        $this->assertEquals([15, 10, 0], array_column($settings['report_card']['scale'], 'min'));
        $this->assertSame('Très bien acquis', DocumentSettings::appreciation($settings['report_card']['scale'], 17));
        $this->assertSame('Acquis', DocumentSettings::appreciation($settings['report_card']['scale'], 12));
    }

    public function test_invalid_color_is_rejected(): void
    {
        $school = School::create(['name' => 'École A', 'slug' => 'a', 'status' => 'active']);
        $admin = $this->makeUser($school, 'school_admin');

        $this->actingAs($admin)
            ->put(route('app.settings.documents.update'), $this->validPayload(['branding' => ['primary_color' => 'red;}body{']]))
            ->assertSessionHasErrors('branding.primary_color');
    }

    public function test_teacher_and_parent_cannot_access_document_settings(): void
    {
        $school = School::create(['name' => 'École A', 'slug' => 'a', 'status' => 'active']);

        foreach (['teacher', 'parent'] as $role) {
            $this->actingAs($this->makeUser($school, $role))
                ->get(route('app.settings.documents'))
                ->assertForbidden();
        }
    }

    public function test_previews_render_as_pdf(): void
    {
        $school = School::create(['name' => 'École A', 'slug' => 'a', 'status' => 'active']);
        $admin = $this->makeUser($school, 'school_admin');

        $this->actingAs($admin)->put(route('app.settings.documents.update'), $this->validPayload());

        foreach (['report_card', 'student_card', 'receipt'] as $type) {
            $this->actingAs($admin)
                ->get(route('app.settings.documents.preview', $type))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }
    }
}
