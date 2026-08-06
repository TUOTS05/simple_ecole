<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Models\SchoolYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FeeController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = $this->schoolId($request);

        $fees = Fee::query()
            ->where('school_id', $schoolId)
            ->with('schoolYear')
            ->when($request->filled('school_year_id'), fn ($query) => $query->where('school_year_id', $request->integer('school_year_id')))
            ->when($request->filled('fee_type'), fn ($query) => $query->where('fee_type', $request->string('fee_type')->toString()))
            ->orderByDesc('school_year_id')
            ->orderBy('level')
            ->get();

        $schoolYears = SchoolYear::where('school_id', $schoolId)->orderByDesc('start_date')->get();

        return view('app.fees.index', compact('fees', 'schoolYears'));
    }

    public function create(Request $request): View
    {
        return view('app.fees.create', $this->formData($request));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['school_id'] = $this->schoolId($request);

        Fee::create($data);

        return redirect()->route('app.fees.index')->with('success', 'Frais créé avec succès.');
    }

    public function show(Request $request, Fee $fee): View
    {
        $this->ensureBelongsToSchool($request, $fee);

        return view('app.fees.show', compact('fee'));
    }

    public function edit(Request $request, Fee $fee): View
    {
        $this->ensureBelongsToSchool($request, $fee);

        return view('app.fees.edit', array_merge(['fee' => $fee], $this->formData($request)));
    }

    public function update(Request $request, Fee $fee): RedirectResponse
    {
        $this->ensureBelongsToSchool($request, $fee);
        $fee->update($this->validated($request));

        return redirect()->route('app.fees.index')->with('success', 'Frais mis à jour avec succès.');
    }

    public function destroy(Request $request, Fee $fee): RedirectResponse
    {
        $this->ensureBelongsToSchool($request, $fee);
        $fee->delete();

        return redirect()->route('app.fees.index')->with('success', 'Frais supprimé avec succès.');
    }

    private function formData(Request $request): array
    {
        $schoolId = $this->schoolId($request);

        return [
            'schoolYears' => SchoolYear::where('school_id', $schoolId)->orderByDesc('start_date')->get(),
            'allowedLevels' => $request->user()->school?->getAllowedLevels() ?? [],
        ];
    }

    private function validated(Request $request): array
    {
        $schoolId = $this->schoolId($request);

        return $request->validate([
            'school_year_id' => [
                'required',
                Rule::exists('school_years', 'id')->where('school_id', $schoolId),
            ],
            'level' => ['required', 'string', Rule::in($request->user()->school?->getAllowedLevels() ?? [])],
            'fee_type' => ['required', Rule::in(['registration', 'tuition'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function schoolId(Request $request): int
    {
        return (int) $request->user()->school_id;
    }

    private function ensureBelongsToSchool(Request $request, Fee $fee): void
    {
        abort_unless($fee->school_id === $this->schoolId($request), 403);
    }
}
