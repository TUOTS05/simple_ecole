<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\ReportCard;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Support\SchoolSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Personnalisation par école : espace, matricules, bulletins, matières.
 */
class SchoolCustomizationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private SchoolYear $year;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['name' => 'La pépite de demain', 'slug' => 'la-pepite-de-demain', 'status' => 'active', 'school_type' => 'both']);
        $this->year = SchoolYear::create(['school_id' => $this->school->id, 'name' => '2026-2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-01', 'is_active' => true]);

        $this->admin = new User(['first_name' => 'Dir', 'last_name' => 'Ecteur', 'email' => 'dir@example.test', 'password' => bcrypt('password')]);
        $this->admin->role = 'school_admin';
        $this->admin->school_id = $this->school->id;
        $this->admin->save();
    }

    /** Formulaire complet tel qu'envoyé par la page, avec les valeurs par défaut. */
    private function payload(array $overrides = []): array
    {
        $scaleRows = fn (array $scale) => array_map(fn ($row) => ['min' => $row['min'], 'label' => $row['label']], $scale);

        return array_replace_recursive([
            'identity_short_name' => '', 'identity_motto' => '', 'identity_city' => '',
            'identity_header_line1' => '', 'identity_header_line2' => '', 'identity_primary_color' => '#87CEEB',
            'matricule_pattern' => '{ANNEE}-{CODE}-{NUM}', 'matricule_code' => '', 'matricule_digits' => 3, 'matricule_start' => 1,
            'admission_pattern' => 'ADM-{ANNEE}-{NUM}', 'admission_digits' => 4, 'admission_start' => 1,
            'report_card_subject_scale' => $scaleRows(SchoolSettings::DEFAULTS['report_card']['subject_scale']),
            'report_card_general_scale' => $scaleRows(SchoolSettings::DEFAULTS['report_card']['general_scale']),
            'report_card_show_rank' => '1', 'report_card_show_class_size' => '1', 'report_card_show_coefficient' => '1',
            'report_card_show_mention' => '1', 'report_card_group_by_domain' => '1',
            'report_card_mention_title' => 'MENTION DU CONSEIL',
            'report_card_left_box_title' => 'APPRÉCIATION GÉNÉRALE & OBSERVATIONS',
            'report_card_right_box_title' => 'LA DIRECTION/ CACHET & SIGNATURE',
            'report_card_director_name' => '', 'report_card_footer_note' => '',
            'subjects_maternelle' => implode("\n", SchoolSettings::DEFAULT_SUBJECTS),
            'subjects_primaire' => implode("\n", SchoolSettings::DEFAULT_SUBJECTS),
        ], $overrides);
    }

    private function makeStudent(string $firstName = 'Awa'): Student
    {
        $student = new Student(['first_name' => $firstName, 'last_name' => 'Koné', 'birth_date' => '2019-01-01', 'gender' => 'F']);
        $student->school_id = $this->school->id;
        $student->save();

        return $student;
    }

    // ------------------------------------------------------------ Page

    public function test_customization_page_saves_settings_and_is_reserved_to_settings_right(): void
    {
        $this->actingAs($this->admin)->get(route('app.settings.customization'))
            ->assertOk()->assertSee('Espace & documents')->assertSee('2026-LAP-001');

        $this->actingAs($this->admin)->put(route('app.settings.customization.update'), $this->payload([
            'identity_short_name' => 'Pépite', 'identity_primary_color' => '#7c3aed', 'identity_city' => 'Bouaké',
        ]))->assertRedirect(route('app.settings.customization'))->assertSessionHasNoErrors();

        $this->school->refresh();
        $this->assertSame('Pépite', $this->school->setting('identity.short_name'));
        $this->assertSame('#7C3AED', $this->school->setting('identity.primary_color'));

        // Couleur et nom court appliqués à l'espace de l'école
        $this->actingAs($this->admin)->get(route('app.subjects.index'))
            ->assertSee('#7C3AED', false)->assertSee('Pépite');

        $accountant = new User(['first_name' => 'C', 'last_name' => 'D', 'email' => 'compta@example.test', 'password' => bcrypt('password')]);
        $accountant->role = 'accountant';
        $accountant->school_id = $this->school->id;
        $accountant->save();
        $this->actingAs($accountant)->get(route('app.settings.customization'))->assertForbidden();
    }

    public function test_invalid_number_formats_are_rejected(): void
    {
        $this->actingAs($this->admin)->put(route('app.settings.customization.update'), $this->payload(['matricule_pattern' => 'MAT-{ANNEE}']))
            ->assertSessionHasErrors(['matricule_pattern' => 'Le format doit contenir {NUM} (le numéro séquentiel).']);

        $this->actingAs($this->admin)->put(route('app.settings.customization.update'), $this->payload(['admission_pattern' => '{ECOLE}-{NUM}']))
            ->assertSessionHasErrors('admission_pattern');
    }

    // ------------------------------------------------------------ Matricules

    public function test_default_matricule_no_longer_has_a_double_dash(): void
    {
        $student = $this->makeStudent();

        $this->assertSame(date('Y').'-LAP-001', $student->matricule);
        $this->assertSame(date('Y').'-LAP-002', $this->makeStudent('Ali')->matricule);
    }

    public function test_custom_matricule_and_admission_formats_are_used(): void
    {
        $this->actingAs($this->admin)->put(route('app.settings.customization.update'), $this->payload([
            'matricule_pattern' => '{CODE}/{AA}/{NUM}', 'matricule_code' => 'gstts', 'matricule_digits' => 4, 'matricule_start' => 50,
            'admission_pattern' => 'INS{ANNEE}{NUM}', 'admission_digits' => 3, 'admission_start' => 1,
        ]))->assertSessionHasNoErrors();

        $yy = substr(date('Y'), -2);
        $this->assertSame("GSTTS/{$yy}/0050", $this->makeStudent()->matricule);
        $this->assertSame("GSTTS/{$yy}/0051", $this->makeStudent('Ali')->matricule);
        $this->assertSame('INS'.date('Y').'001', Student::generateAdmissionNumber($this->school->id));
    }

    // ------------------------------------------------------------ Matières

    public function test_subject_template_order_domain_and_copy(): void
    {
        // Liste de matières propre à l'école, proposée à la configuration d'un niveau
        $this->actingAs($this->admin)->put(route('app.settings.customization.update'), $this->payload([
            'subjects_primaire' => "Lecture\nCalcul mental\n\nLecture",
        ]))->assertSessionHasNoErrors();
        $this->assertSame(['Lecture', 'Calcul mental'], $this->school->fresh()->setting('subjects.primaire'));
        $this->actingAs($this->admin)->get(route('app.subjects.create'))->assertSee('Calcul mental');

        // Enregistrement avec domaine : l'ordre suit le formulaire
        $this->actingAs($this->admin)->post(route('app.subjects.store'), [
            'cycle' => 'primaire', 'level' => 'CP1',
            'subjects' => [
                ['name' => 'Lecture', 'domain' => 'Français', 'coefficient' => 2, 'max_score' => 20],
                ['name' => 'Calcul mental', 'domain' => 'Mathématiques', 'coefficient' => 1, 'max_score' => 10],
            ],
        ])->assertSessionHasNoErrors();

        $lecture = Subject::where('name', 'Lecture')->firstOrFail();
        $calcul = Subject::where('name', 'Calcul mental')->firstOrFail();
        $this->assertSame('Français', $lecture->domain);
        $this->assertTrue($lecture->sort_order < $calcul->sort_order);

        // Réordonner et changer un domaine depuis la liste
        $this->actingAs($this->admin)->put(route('app.subjects.bulk-update'), ['subjects' => [
            $lecture->id => ['sort_order' => 5, 'domain' => 'Langue', 'coefficient' => 3, 'max_score' => 20],
            $calcul->id => ['sort_order' => 1, 'domain' => 'Mathématiques', 'coefficient' => 1, 'max_score' => 10],
        ]])->assertSessionHasNoErrors();
        $this->assertSame(['Calcul mental', 'Lecture'], Subject::where('level', 'CP1')->ordered()->pluck('name')->all());
        $this->assertSame('Langue', $lecture->fresh()->domain);

        // Copier CP1 vers CP2 et CE1 (sans doublon si on recommence)
        $this->actingAs($this->admin)->post(route('app.subjects.copy'), [
            'source' => $this->year->id.'|CP1', 'target_levels' => ['CP2', 'CE1'],
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->actingAs($this->admin)->post(route('app.subjects.copy'), [
            'source' => $this->year->id.'|CP1', 'target_levels' => ['CP2'],
        ]);

        $this->assertSame(2, Subject::where('level', 'CP2')->count());
        $copied = Subject::where('level', 'CE1')->where('name', 'Lecture')->firstOrFail();
        $this->assertSame('Langue', $copied->domain);
        $this->assertSame(5, $copied->sort_order);
        $this->assertSame('primaire', $copied->cycle);
    }

    // ------------------------------------------------------------ Bulletins

    private function renderReportCard(): string
    {
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'CP1 A', 'level' => 'CP1', 'cycle' => 'primaire']);
        $student = $this->makeStudent();
        $subjects = collect([
            Subject::create(['school_id' => $this->school->id, 'school_year_id' => $this->year->id, 'cycle' => 'primaire', 'level' => 'CP1', 'name' => 'Lecture', 'domain' => 'Français', 'coefficient' => 1, 'max_score' => 20, 'sort_order' => 1]),
            Subject::create(['school_id' => $this->school->id, 'school_year_id' => $this->year->id, 'cycle' => 'primaire', 'level' => 'CP1', 'name' => 'Calcul', 'domain' => 'Mathématiques', 'coefficient' => 1, 'max_score' => 20, 'sort_order' => 2]),
        ]);

        $reportCard = new ReportCard(['period' => 'Trimestriel', 'quarter' => 1, 'rank' => 3, 'total_students' => 25]);
        $reportCard->setRelation('grades', collect([
            new Grade(['subject_id' => $subjects[0]->id, 'score' => 15, 'max_score' => 20, 'coefficient_used' => 1]),
            new Grade(['subject_id' => $subjects[1]->id, 'score' => 11, 'max_score' => 20, 'coefficient_used' => 1]),
        ]));

        return view('pdf.report-card', [
            'reportCard' => $reportCard, 'student' => $student, 'school' => $this->school->fresh(),
            'schoolYear' => $this->year, 'schoolClass' => $class, 'allSubjects' => $subjects,
        ])->render();
    }

    public function test_report_card_defaults_are_unchanged(): void
    {
        $html = $this->renderReportCard();

        $this->assertStringContainsString('CLASSEMENT (RANG)', $html);
        $this->assertStringContainsString('MENTION DU CONSEIL', $html);
        $this->assertStringContainsString('Bien', $html);              // 15/20 → Bien
        $this->assertStringContainsString('Assez Bien', $html);        // moyenne 13 → Assez Bien
        $this->assertStringContainsString('Fait à: Abidjan', $html);
        $this->assertStringContainsString('Français</td>', $html);      // regroupement par domaine
    }

    public function test_report_card_uses_school_customization(): void
    {
        $this->actingAs($this->admin)->put(route('app.settings.customization.update'), $this->payload([
            'identity_header_line1' => "République de Côte d'Ivoire", 'identity_motto' => 'Travail et réussite', 'identity_city' => 'Yamoussoukro',
            'report_card_show_rank' => '0', 'report_card_show_coefficient' => '0', 'report_card_group_by_domain' => '0',
            'report_card_general_scale' => [['min' => 12, 'label' => 'Félicitations'], ['min' => 0, 'label' => 'Peut mieux faire'], ['min' => '', 'label' => ''], ['min' => '', 'label' => ''], ['min' => '', 'label' => '']],
            'report_card_mention_title' => 'DÉCISION',
            'report_card_right_box_title' => 'LE DIRECTEUR',
            'report_card_director_name' => 'M. TUO Seydou',
            'report_card_footer_note' => 'Aucun duplicata ne sera délivré.',
        ]))->assertSessionHasNoErrors();

        $html = $this->renderReportCard();

        $this->assertStringContainsString('République de Côte d&#039;Ivoire', $html);
        $this->assertStringContainsString('Travail et réussite', $html);
        $this->assertStringContainsString('Fait à: Yamoussoukro', $html);
        $this->assertStringNotContainsString('CLASSEMENT (RANG)', $html);
        $this->assertStringNotContainsString('>COEFF<', $html);
        $this->assertStringContainsString('DÉCISION :', $html);
        $this->assertStringContainsString('Félicitations', $html);    // moyenne 13 ≥ 12
        $this->assertStringContainsString('LE DIRECTEUR', $html);
        $this->assertStringContainsString('M. TUO Seydou', $html);
        $this->assertStringContainsString('Aucun duplicata ne sera délivré.', $html);
        $this->assertStringNotContainsString('Mathématiques</td>', $html);
    }
}
