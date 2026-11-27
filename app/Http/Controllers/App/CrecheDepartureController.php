<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Student;
use App\Services\CrecheAttendanceService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Crèche > Départ enfant : retrait sécurisé. On retrouve l'enfant (nom ou matricule), on voit sa
 * photo, son groupe et les personnes autorisées, on choisit la personne venue le chercher :
 * DÉPART AUTORISÉ (départ enregistré) ou DÉPART BLOQUÉ (alerte + tentative journalisée).
 */
class CrecheDepartureController extends Controller
{
    public function __construct(private CrecheAttendanceService $attendances) {}

    /** Recherche de l'enfant : par défaut, les enfants encore présents. */
    public function index(Request $request)
    {
        $school = $this->school();
        $groupIds = $request->user()->accessibleCrecheClassIds();
        $search = trim((string) $request->query('search'));

        $children = Student::where('school_id', $school->id)
            ->whereHas('classes', fn ($q) => $q->whereIn('school_classes.id', $groupIds))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('matricule', $search)))
            ->when($search === '', fn ($q) => $q->whereHas('crecheAttendances', fn ($q) => $q->whereDate('date', today())->where('status', 'present')))
            ->with(['classes', 'crecheAttendances' => fn ($q) => $q->whereDate('date', today())])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(50)
            ->get();

        // Matricule exact (scan ou saisie) : on ouvre directement la fiche de départ.
        if ($search !== '' && $children->count() === 1 && $children->first()->matricule === $search) {
            return redirect()->route('app.creche.departure.show', $children->first());
        }

        return view('app.creche.departure.index', compact('children', 'search'));
    }

    public function show(Student $student)
    {
        $this->school();
        Gate::authorize('recordCrecheAttendance', $student);

        $student->load(['classes', 'careProfile']);

        return view('app.creche.departure.show', [
            'student' => $student,
            'group' => $student->crecheClass(),
            'attendance' => $this->attendances->today($student),
            'pickups' => $student->authorizedPickups()->orderByRaw("status = 'forbidden' desc")->orderBy('last_name')->get(),
            'result' => session('departure_result'),
        ]);
    }

    public function store(Request $request, Student $student)
    {
        $this->school();
        Gate::authorize('recordCrecheAttendance', $student);

        $data = $request->validate([
            'pickup_id' => 'required|string',
            'other_name' => 'nullable|required_if:pickup_id,other|string|max:150',
            'identity_checked' => 'nullable|boolean',
        ], [
            'pickup_id.required' => 'Choisissez la personne venue chercher l\'enfant.',
            'other_name.required_if' => 'Indiquez le nom de la personne qui se présente.',
        ]);

        $pickupId = ctype_digit($data['pickup_id']) ? (int) $data['pickup_id'] : null;

        try {
            $outcome = $this->attendances->checkOut($student, $request->user(), $pickupId, $request->boolean('identity_checked'), $data['other_name'] ?? null);
        } catch (DomainException $e) {
            return redirect()->route('app.creche.departure.show', $student)->withErrors(['departure' => $e->getMessage()]);
        }

        return redirect()->route('app.creche.departure.show', $student)->with('departure_result', [
            'allowed' => $outcome['allowed'],
            'reason' => $outcome['reason'],
        ]);
    }

    private function school(): School
    {
        $school = School::findOrFail(session('current_school_id'));
        abort_unless($school->isCreche(), 404);

        return $school;
    }
}
