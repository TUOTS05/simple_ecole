<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Support\SchoolNumbering;
use App\Support\SchoolSettings;
use Closure;
use Illuminate\Http\Request;

/**
 * Page « Personnalisation » de l'école : espace (couleur, sigle, en-têtes des documents),
 * formats des matricules et numéros d'admission, bulletins, liste de matières par défaut.
 * Tout est stocké dans schools.settings (voir App\Support\SchoolSettings).
 */
class SchoolCustomizationController extends Controller
{
    public function edit()
    {
        $school = School::findOrFail(session('current_school_id'));

        return view('app.settings.customization', [
            'school' => $school,
            's' => fn (string $key) => old(str_replace('.', '_', $key), SchoolSettings::get($school, $key)),
            'tokens' => SchoolSettings::TOKENS,
            'defaultCode' => SchoolSettings::defaultCode($school),
            'nextMatricule' => SchoolNumbering::next($school, 'matricule'),
            'nextAdmission' => SchoolNumbering::next($school, 'admission'),
        ]);
    }

    public function update(Request $request)
    {
        $school = School::findOrFail(session('current_school_id'));

        $patternRule = function (string $attribute, mixed $value, Closure $fail) {
            $value = (string) $value;
            if (! str_contains($value, '{NUM}')) {
                $fail('Le format doit contenir {NUM} (le numéro séquentiel).');

                return;
            }
            $rest = str_replace(array_keys(SchoolSettings::TOKENS), '', $value);
            if (str_contains($rest, '{') || str_contains($rest, '}')) {
                $fail('Le format contient une balise inconnue. Balises possibles : '.implode(', ', array_keys(SchoolSettings::TOKENS)).'.');
            }
            if (! preg_match('/^[A-Za-z0-9\-\/_. ]*$/', $rest)) {
                $fail('Le format ne peut contenir que des lettres, chiffres et les séparateurs - / _ . (en plus des balises).');
            }
        };

        $v = $request->validate([
            'identity_short_name' => 'nullable|string|max:30',
            'identity_motto' => 'nullable|string|max:150',
            'identity_city' => 'nullable|string|max:80',
            'identity_header_line1' => 'nullable|string|max:120',
            'identity_header_line2' => 'nullable|string|max:120',
            'identity_primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],

            'matricule_pattern' => ['required', 'string', 'max:40', $patternRule],
            'matricule_code' => 'nullable|alpha_num|max:6',
            'matricule_digits' => 'required|integer|min:1|max:8',
            'matricule_start' => 'required|integer|min:1|max:99999999',
            'admission_pattern' => ['required', 'string', 'max:40', $patternRule],
            'admission_digits' => 'required|integer|min:1|max:8',
            'admission_start' => 'required|integer|min:1|max:99999999',

            'report_card_subject_scale' => 'required|array',
            'report_card_subject_scale.*.min' => 'nullable|numeric|min:0|max:20',
            'report_card_subject_scale.*.label' => 'nullable|string|max:40',
            'report_card_general_scale' => 'required|array',
            'report_card_general_scale.*.min' => 'nullable|numeric|min:0|max:20',
            'report_card_general_scale.*.label' => 'nullable|string|max:40',
            'report_card_mention_title' => 'required|string|max:60',
            'report_card_left_box_title' => 'required|string|max:80',
            'report_card_right_box_title' => 'required|string|max:80',
            'report_card_director_name' => 'nullable|string|max:100',
            'report_card_footer_note' => 'nullable|string|max:200',

            'subjects_maternelle' => 'nullable|string|max:3000',
            'subjects_primaire' => 'nullable|string|max:3000',
        ], [
            'identity_primary_color.regex' => 'La couleur doit être au format #RRGGBB.',
            'matricule_code.alpha_num' => "Le code de l'école ne peut contenir que des lettres et des chiffres.",
            'report_card_subject_scale.*.min.max' => 'Les seuils des mentions sont des notes sur 20.',
            'report_card_general_scale.*.min.max' => 'Les seuils des mentions sont des notes sur 20.',
        ]);

        $subjectScale = $this->scale($v['report_card_subject_scale']);
        $generalScale = $this->scale($v['report_card_general_scale']);
        if (! $subjectScale || ! $generalScale) {
            return back()->withErrors(['report_card_subject_scale' => 'Chaque barème de mentions doit avoir au moins une ligne (seuil + libellé).'])->withInput();
        }

        $settings = $school->settings ?? [];
        $settings['identity'] = [
            'short_name' => $v['identity_short_name'],
            'motto' => $v['identity_motto'],
            'city' => $v['identity_city'],
            'header_line1' => $v['identity_header_line1'],
            'header_line2' => $v['identity_header_line2'],
            'primary_color' => strtoupper($v['identity_primary_color']),
        ];
        $settings['matricule'] = [
            'pattern' => trim($v['matricule_pattern']),
            'code' => $v['matricule_code'] ? strtoupper($v['matricule_code']) : null,
            'digits' => (int) $v['matricule_digits'],
            'start' => (int) $v['matricule_start'],
        ];
        $settings['admission'] = [
            'pattern' => trim($v['admission_pattern']),
            'digits' => (int) $v['admission_digits'],
            'start' => (int) $v['admission_start'],
        ];
        $settings['report_card'] = [
            'subject_scale' => $subjectScale,
            'general_scale' => $generalScale,
            'show_rank' => $request->boolean('report_card_show_rank'),
            'show_class_size' => $request->boolean('report_card_show_class_size'),
            'show_coefficient' => $request->boolean('report_card_show_coefficient'),
            'show_mention' => $request->boolean('report_card_show_mention'),
            'group_by_domain' => $request->boolean('report_card_group_by_domain'),
            'mention_title' => $v['report_card_mention_title'],
            'left_box_title' => $v['report_card_left_box_title'],
            'right_box_title' => $v['report_card_right_box_title'],
            'director_name' => $v['report_card_director_name'],
            'footer_note' => $v['report_card_footer_note'],
        ];
        $settings['subjects'] = [
            'maternelle' => $this->lines($v['subjects_maternelle'] ?? ''),
            'primaire' => $this->lines($v['subjects_primaire'] ?? ''),
        ];

        $school->settings = $settings;
        $school->save();

        return redirect()->route('app.settings.customization')
            ->with('success', '✅ Personnalisation enregistrée.');
    }

    /** Lignes complètes (seuil + libellé), triées du seuil le plus haut au plus bas. */
    private function scale(array $rows): array
    {
        $scale = collect($rows)
            ->filter(fn ($row) => ($row['label'] ?? '') !== '' && ($row['min'] ?? '') !== '' && $row['label'] !== null && $row['min'] !== null)
            ->map(fn ($row) => ['min' => (float) $row['min'], 'label' => trim($row['label'])])
            ->sortByDesc('min')
            ->values()
            ->all();

        return $scale;
    }

    /** Une matière par ligne, sans doublons ni lignes vides (50 au maximum). */
    private function lines(string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $text))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->unique()
            ->take(50)
            ->values()
            ->all();
    }
}
