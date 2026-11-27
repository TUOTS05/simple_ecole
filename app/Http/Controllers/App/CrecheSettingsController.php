<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

/**
 * Crèche > Paramètres : groupes d'âge proposés, horaires par défaut, contrôle de capacité.
 * Stocké dans schools.settings['creche'] (voir App\Support\SchoolSettings).
 */
class CrecheSettingsController extends Controller
{
    public function edit()
    {
        $school = $this->school();

        return view('app.creche.settings', [
            'school' => $school,
            'levels' => old('levels', implode("\n", $school->crecheLevels())),
            'openingTime' => old('opening_time', $school->setting('creche.opening_time')),
            'closingTime' => old('closing_time', $school->setting('creche.closing_time')),
            'enforceCapacity' => (bool) old('enforce_capacity', $school->setting('creche.enforce_capacity')),
            'activityCategories' => old('activity_categories', implode("\n", (array) $school->setting('creche.activity_categories'))),
            'hygieneTypes' => old('hygiene_types', implode("\n", (array) $school->setting('creche.hygiene_types'))),
            'mealTime' => old('meal_time', $school->setting('creche.meal_time')),
            'snackTime' => old('snack_time', $school->setting('creche.snack_time')),
            'diaperInterval' => old('diaper_interval_hours', $school->setting('creche.diaper_interval_hours')),
            // Niveaux encore portés par des groupes : on prévient avant qu'ils disparaissent de la liste.
            'levelsInUse' => SchoolClass::where('school_id', $school->id)->creche()->distinct()->pluck('level')->all(),
        ]);
    }

    public function update(Request $request)
    {
        $school = $this->school();

        $v = $request->validate([
            'levels' => 'required|string|max:2000',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i|after:opening_time',
            'enforce_capacity' => 'nullable|boolean',
            // Journal quotidien (champs absents du formulaire : valeur actuelle conservée)
            'activity_categories' => 'nullable|string|max:2000',
            'hygiene_types' => 'nullable|string|max:2000',
            'meal_time' => 'nullable|date_format:H:i',
            'snack_time' => 'nullable|date_format:H:i',
            'diaper_interval_hours' => 'nullable|integer|min:1|max:8',
        ], [
            'levels.required' => 'Indiquez au moins un groupe d\'âge.',
            'closing_time.after' => 'L\'heure de fermeture doit être après l\'heure d\'ouverture.',
            'diaper_interval_hours.max' => 'Le délai entre deux changes doit être de 8 heures au plus.',
        ]);

        $lists = [
            'levels' => ['Indiquez au moins un groupe d\'âge.', 30, 20],
            'activity_categories' => ['Indiquez au moins une catégorie d\'activité.', 40, 30],
            'hygiene_types' => ['Indiquez au moins un type de soin d\'hygiène.', 40, 30],
        ];
        $parsed = [];
        foreach ($lists as $field => [$emptyMessage, $maxLength, $maxItems]) {
            if (! array_key_exists($field, $v) || $v[$field] === null) {
                $parsed[$field] = (array) $school->setting("creche.{$field}");

                continue;
            }

            $items = $this->lines($v[$field]);
            if ($items->isEmpty()) {
                return back()->withErrors([$field => $emptyMessage])->withInput();
            }
            if ($items->contains(fn ($item) => mb_strlen($item) > $maxLength)) {
                return back()->withErrors([$field => "Chaque ligne doit faire {$maxLength} caractères au maximum."])->withInput();
            }
            $parsed[$field] = $items->take($maxItems)->all();
        }

        $settings = $school->settings ?? [];
        $settings['creche'] = [
            'levels' => $parsed['levels'],
            'opening_time' => $v['opening_time'],
            'closing_time' => $v['closing_time'],
            'enforce_capacity' => $request->boolean('enforce_capacity'),
            'activity_categories' => $parsed['activity_categories'],
            'hygiene_types' => $parsed['hygiene_types'],
            'meal_time' => $v['meal_time'] ?? $school->setting('creche.meal_time'),
            'snack_time' => $v['snack_time'] ?? $school->setting('creche.snack_time'),
            'diaper_interval_hours' => (int) ($v['diaper_interval_hours'] ?? $school->setting('creche.diaper_interval_hours')),
        ];
        $school->settings = $settings;
        $school->save();

        ActivityLog::audit('creche.settings.updated', 'A modifié les paramètres de la crèche', $school);

        return redirect()->route('app.creche.settings.edit')
            ->with('success', '✅ Paramètres de la crèche enregistrés.');
    }

    /** Une valeur par ligne, sans doublons ni lignes vides. */
    private function lines(string $text)
    {
        return collect(preg_split('/\r\n|\r|\n/', $text))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->unique()
            ->values();
    }

    private function school(): School
    {
        $school = School::findOrFail(session('current_school_id'));
        abort_unless($school->isCreche(), 404);

        return $school;
    }
}
