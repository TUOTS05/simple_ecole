<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AuthorizedPickup;
use App\Models\Student;
use App\Support\PrivateFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Personnes autorisées (ou interdites) à récupérer un enfant de la crèche. Chaque ajout,
 * modification ou retrait est inscrit au journal d'audit ; un retrait est une suppression
 * douce pour garder la trace des départs passés.
 */
class AuthorizedPickupController extends Controller
{
    public function store(Request $request, Student $student)
    {
        Gate::authorize('updateCrecheRecord', $student);
        abort_unless($student->classes()->where('cycle', 'creche')->exists(), 404);

        $validated = $this->validatePickup($request);

        $pickup = new AuthorizedPickup($validated);
        $pickup->school_id = $student->school_id;
        $pickup->student_id = $student->id;
        $pickup->created_by = $request->user()->id;
        $pickup->updated_by = $request->user()->id;
        if ($request->hasFile('photo')) {
            $pickup->photo = PrivateFiles::store($request->file('photo'), 'creche/pickups');
        }
        $pickup->save();

        ActivityLog::audit(
            'creche.pickup.created',
            "A ajouté {$pickup->full_name} ({$pickup->relationship}, ".mb_strtolower(AuthorizedPickup::STATUSES[$pickup->status]).") pour {$student->first_name} {$student->last_name}",
            $student,
            ['pickup_id' => $pickup->id, 'after' => $pickup->auditSnapshot()]
        );

        return redirect()->route('app.creche.children.show', $student)
            ->with('success', "✅ {$pickup->full_name} ajouté(e) aux personnes autorisées.");
    }

    public function update(Request $request, AuthorizedPickup $pickup)
    {
        $student = $this->studentOf($pickup);
        Gate::authorize('updateCrecheRecord', $student);

        $validated = $this->validatePickup($request);
        $before = $pickup->auditSnapshot();

        $pickup->fill($validated);
        $pickup->updated_by = $request->user()->id;
        if ($request->hasFile('photo')) {
            PrivateFiles::delete($pickup->photo);
            $pickup->photo = PrivateFiles::store($request->file('photo'), 'creche/pickups');
        } elseif ($request->boolean('remove_photo')) {
            PrivateFiles::delete($pickup->photo);
            $pickup->photo = null;
        }
        $pickup->save();

        ActivityLog::audit(
            'creche.pickup.updated',
            "A modifié la personne autorisée {$pickup->full_name} de {$student->first_name} {$student->last_name}",
            $student,
            ['pickup_id' => $pickup->id, 'before' => $before, 'after' => $pickup->auditSnapshot()]
        );

        return redirect()->route('app.creche.children.show', $student)
            ->with('success', "✅ {$pickup->full_name} mis(e) à jour.");
    }

    public function destroy(Request $request, AuthorizedPickup $pickup)
    {
        $student = $this->studentOf($pickup);
        Gate::authorize('updateCrecheRecord', $student);

        $pickup->updated_by = $request->user()->id;
        $pickup->save();
        $pickup->delete();

        ActivityLog::audit(
            'creche.pickup.deleted',
            "A retiré {$pickup->full_name} des personnes autorisées de {$student->first_name} {$student->last_name}",
            $student,
            ['pickup_id' => $pickup->id, 'before' => $pickup->auditSnapshot()]
        );

        return redirect()->route('app.creche.children.show', $student)
            ->with('success', "{$pickup->full_name} a été retiré(e) de la liste.");
    }

    public function photo(AuthorizedPickup $pickup)
    {
        Gate::authorize('viewCrecheRecord', $this->studentOf($pickup));

        return PrivateFiles::response($pickup->photo);
    }

    /** Élève de la personne autorisée, en restant dans l'école de l'utilisateur. */
    private function studentOf(AuthorizedPickup $pickup): Student
    {
        abort_unless($pickup->school_id === session('current_school_id'), 404);

        return Student::findOrFail($pickup->student_id);
    }

    private function validatePickup(Request $request): array
    {
        return $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9][0-9 .\-]{7,19}$/'],
            'relationship' => 'required|string|max:50',
            'status' => ['required', Rule::in(array_keys(AuthorizedPickup::STATUSES))],
            'starts_on' => 'nullable|date',
            'ends_on' => 'nullable|date|after_or_equal:starts_on',
            'comment' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'phone.regex' => 'Le numéro de téléphone ne doit contenir que des chiffres (8 minimum), éventuellement précédés de +.',
            'ends_on.after_or_equal' => 'La date de fin doit être postérieure à la date de début.',
            'photo.image' => 'La photo doit être une image (JPG ou PNG).',
            'photo.max' => 'La photo ne doit pas dépasser 2 Mo.',
        ]);
    }
}
