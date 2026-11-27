<?php

namespace App\Support;

use App\Models\ChildCareProfile;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Inscription d'un enfant dans un groupe de crèche : ouvre son dossier crèche (sans dupliquer
 * la fiche élève), puis enchaîne vers ce dossier pour saisir les personnes autorisées.
 */
class CrecheEnrollment
{
    /** Crée le dossier crèche si la classe est un groupe de crèche. Retourne true dans ce cas. */
    public static function start(Student $student, int $classId, mixed $entryDate = null): bool
    {
        if (! SchoolClass::whereKey($classId)->where('cycle', 'creche')->exists()) {
            return false;
        }

        if (! ChildCareProfile::where('student_id', $student->id)->exists()) {
            // student_id / school_id ne sont pas assignables en masse : on les affecte un par un.
            $profile = new ChildCareProfile;
            $profile->student_id = $student->id;
            $profile->school_id = $student->school_id;
            $profile->entry_date = $entryDate;
            $profile->updated_by = auth()->id();
            $profile->save();
        }

        return true;
    }

    /** Après l'inscription d'un enfant de crèche : direction son dossier, si l'utilisateur y a accès. */
    public static function redirectAfterEnrollment(Student $student, string $success): ?RedirectResponse
    {
        if (! Gate::allows('updateCrecheRecord', $student)) {
            return null;
        }

        return redirect()->route('app.creche.children.show', $student)
            ->with('success', $success.' Ajoutez maintenant les personnes autorisées à récupérer l\'enfant.');
    }
}
