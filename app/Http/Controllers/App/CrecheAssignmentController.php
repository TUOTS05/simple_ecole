<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CareStaffAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Crèche > Affectations : quel membre du personnel s'occupe de quel groupe. Un auxiliaire ou
 * l'accueil ne voit que les enfants des groupes auxquels il est affecté.
 */
class CrecheAssignmentController extends Controller
{
    public function index()
    {
        $school = $this->school();

        $groups = SchoolClass::where('school_id', $school->id)
            ->creche()
            ->withCount('students')
            ->with(['careStaffAssignments.user', 'responsible'])
            ->orderBy('name')
            ->get();

        return view('app.creche.assignments', [
            'groups' => $groups,
            'candidates' => $this->candidates($school),
        ]);
    }

    public function store(Request $request)
    {
        $school = $this->school();

        $validated = $request->validate([
            'school_class_id' => ['required', 'integer', Rule::exists('school_classes', 'id')->where('school_id', $school->id)->where('cycle', 'creche')],
            'user_id' => ['required', 'integer', Rule::in($this->candidates($school)->pluck('id')->all())],
            'is_referent' => 'nullable|boolean',
        ], [
            'school_class_id.exists' => 'Ce groupe de crèche n\'existe pas.',
            'user_id.in' => 'Cette personne n\'a pas de droit sur la crèche : donnez-lui un rôle crèche dans Personnel.',
        ]);

        $assignment = CareStaffAssignment::firstOrNew([
            'school_class_id' => $validated['school_class_id'],
            'user_id' => $validated['user_id'],
        ]);
        $assignment->school_id = $school->id;
        $assignment->is_referent = $request->boolean('is_referent');
        $assignment->save();

        $assignment->load('user', 'schoolClass');
        ActivityLog::audit(
            'creche.assignment.saved',
            "A affecté {$assignment->user->full_name} au groupe {$assignment->schoolClass->name}",
            $assignment,
            ['school_class_id' => $assignment->school_class_id, 'user_id' => $assignment->user_id, 'is_referent' => $assignment->is_referent]
        );

        return redirect()->route('app.creche.assignments.index')
            ->with('success', "✅ {$assignment->user->full_name} est affecté(e) au groupe {$assignment->schoolClass->name}.");
    }

    public function destroy(CareStaffAssignment $assignment)
    {
        $school = $this->school();
        abort_unless($assignment->school_id === $school->id, 404);

        $assignment->load('user', 'schoolClass');
        ActivityLog::audit(
            'creche.assignment.removed',
            "A retiré {$assignment->user?->full_name} du groupe {$assignment->schoolClass?->name}",
            $assignment,
            ['school_class_id' => $assignment->school_class_id, 'user_id' => $assignment->user_id]
        );
        $assignment->delete();

        return redirect()->route('app.creche.assignments.index')
            ->with('success', 'Affectation retirée.');
    }

    /** Personnel ayant au moins un droit sur la crèche (rôle crèche ou droits personnalisés). */
    private function candidates(School $school)
    {
        return User::where('school_id', $school->id)
            ->whereIn('role', array_keys(Permissions::ROLES))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->filter(fn (User $user) => $user->hasPermission('creche.view') || $user->hasPermission('creche.attendance'))
            ->values();
    }

    private function school(): School
    {
        $school = School::findOrFail(session('current_school_id'));
        abort_unless($school->isCreche(), 404);

        return $school;
    }
}
