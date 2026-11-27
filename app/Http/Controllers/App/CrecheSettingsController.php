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
        ], [
            'levels.required' => 'Indiquez au moins un groupe d\'âge.',
            'closing_time.after' => 'L\'heure de fermeture doit être après l\'heure d\'ouverture.',
        ]);

        $levels = collect(preg_split('/\r\n|\r|\n/', $v['levels']))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->unique()
            ->values();

        if ($levels->isEmpty()) {
            return back()->withErrors(['levels' => 'Indiquez au moins un groupe d\'âge.'])->withInput();
        }
        if ($levels->contains(fn ($level) => mb_strlen($level) > 30)) {
            return back()->withErrors(['levels' => 'Chaque groupe d\'âge doit faire 30 caractères au maximum.'])->withInput();
        }

        $settings = $school->settings ?? [];
        $settings['creche'] = [
            'levels' => $levels->take(20)->all(),
            'opening_time' => $v['opening_time'],
            'closing_time' => $v['closing_time'],
            'enforce_capacity' => $request->boolean('enforce_capacity'),
        ];
        $school->settings = $settings;
        $school->save();

        ActivityLog::audit('creche.settings.updated', 'A modifié les paramètres de la crèche', $school);

        return redirect()->route('app.creche.settings.edit')
            ->with('success', '✅ Paramètres de la crèche enregistrés.');
    }

    private function school(): School
    {
        $school = School::findOrFail(session('current_school_id'));
        abort_unless($school->isCreche(), 404);

        return $school;
    }
}
