<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AuthorizedPickup;
use App\Models\ChildCareProfile;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Crèche > Enfants : liste des enfants des groupes accessibles et « Dossier crèche » de chacun
 * (profil de prise en charge + personnes autorisées). La fiche élève commune reste inchangée.
 */
class CrecheChildController extends Controller
{
    public function index(Request $request)
    {
        $school = $this->school();
        $groupIds = $request->user()->hasPermission('students.manage')
            ? SchoolClass::where('school_id', $school->id)->creche()->pluck('id')->all()
            : $request->user()->accessibleCrecheClassIds();

        $groups = SchoolClass::whereIn('id', $groupIds)->orderBy('name')->get();
        $groupId = $request->integer('group') ?: null;
        if ($groupId && ! in_array($groupId, $groupIds, true)) {
            abort(403, 'Vous n\'êtes pas affecté(e) à ce groupe.');
        }

        $children = Student::where('school_id', $school->id)
            ->whereHas('classes', fn ($q) => $q->whereIn('school_classes.id', $groupId ? [$groupId] : $groupIds))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%"));
            })
            ->with(['classes', 'careProfile'])
            ->withCount(['authorizedPickups as authorized_count' => fn ($q) => $q->where('status', 'authorized')])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(30)
            ->withQueryString();

        return view('app.creche.children.index', compact('groups', 'groupId', 'children'));
    }

    public function show(Student $student)
    {
        $this->school();
        $this->ensureCrecheChild($student);
        Gate::authorize('viewCrecheRecord', $student);

        $student->load(['classes', 'parents', 'careProfile.referent', 'careProfile.updatedBy']);
        $pickups = $student->authorizedPickups()->orderByRaw("status = 'forbidden' desc")->orderBy('last_name')->get();

        return view('app.creche.children.show', [
            'student' => $student,
            'profile' => $student->careProfile ?? new ChildCareProfile,
            'pickups' => $pickups,
            'canEdit' => Gate::allows('updateCrecheRecord', $student),
            'relationships' => AuthorizedPickup::RELATIONSHIPS,
        ]);
    }

    public function edit(Student $student)
    {
        $school = $this->school();
        $this->ensureCrecheChild($student);
        Gate::authorize('updateCrecheRecord', $student);

        return view('app.creche.children.edit', [
            'student' => $student,
            'profile' => $student->careProfile ?? new ChildCareProfile(['entry_date' => $student->admission_date]),
            'referents' => $this->referentCandidates($school),
        ]);
    }

    public function update(Request $request, Student $student)
    {
        $school = $this->school();
        $this->ensureCrecheChild($student);
        Gate::authorize('updateCrecheRecord', $student);

        $text = 'nullable|string|max:2000';
        $validated = $request->validate([
            'entry_date' => 'nullable|date',
            'care_arrangement' => ['nullable', Rule::in(array_keys(ChildCareProfile::CARE_ARRANGEMENTS))],
            'referent_user_id' => ['nullable', 'integer', Rule::in($this->referentCandidates($school)->pluck('id')->all())],
            'milk_type' => 'nullable|string|max:100',
            'usual_quantity' => 'nullable|string|max:50',
            'feeding_frequency' => 'nullable|string|max:100',
            'introduced_foods' => $text,
            'forbidden_foods' => $text,
            'allergies' => $text,
            'food_preferences' => $text,
            'usual_sleep_time' => 'nullable|string|max:50',
            'usual_sleep_duration' => 'nullable|string|max:50',
            'sleep_ritual' => $text,
            'comforter' => 'nullable|string|max:150',
            'sleep_notes' => $text,
            'changing_habits' => $text,
            'specific_products' => $text,
            'cream' => 'nullable|string|max:150',
            'hygiene_notes' => $text,
            'particularities' => $text,
        ], [
            'referent_user_id.in' => 'La personne référente doit faire partie du personnel de la crèche.',
        ]);

        $profile = $student->careProfile ?? new ChildCareProfile;
        $profile->fill($validated);
        $profile->breastfed = $request->boolean('breastfed');
        $profile->formula_fed = $request->boolean('formula_fed');
        $profile->pacifier = $request->boolean('pacifier');
        $profile->school_id = $student->school_id;
        $profile->student_id = $student->id;
        $profile->updated_by = $request->user()->id;

        $changed = array_keys($profile->getDirty());
        $profile->save();

        ActivityLog::audit(
            'creche.profile.updated',
            "A modifié le dossier crèche de {$student->first_name} {$student->last_name}",
            $student,
            ['fields' => array_values(array_diff($changed, ['updated_by', 'school_id', 'student_id']))]
        );

        return redirect()->route('app.creche.children.show', $student)
            ->with('success', '✅ Dossier crèche enregistré.');
    }

    /** Personnel pouvant être la personne référente de l'enfant. */
    private function referentCandidates(School $school)
    {
        return User::where('school_id', $school->id)
            ->whereIn('role', ['school_admin', ...array_keys(Permissions::ROLES)])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->filter(fn (User $user) => $user->isSchoolAdmin() || $user->hasPermission('creche.view') || $user->hasPermission('creche.manage'))
            ->values();
    }

    /** Le dossier crèche n'existe que pour un enfant inscrit dans un groupe de crèche. */
    private function ensureCrecheChild(Student $student): void
    {
        abort_unless($student->classes()->where('cycle', 'creche')->exists(), 404);
    }

    private function school(): School
    {
        $school = School::findOrFail(session('current_school_id'));
        abort_unless($school->isCreche(), 404);

        return $school;
    }
}
