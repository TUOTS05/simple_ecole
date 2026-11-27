<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Support\PrivateFiles;
use Illuminate\Support\Facades\Gate;

/**
 * Photo et documents d'un élève, servis depuis le stockage privé après contrôle des droits
 * (jamais par une URL publique directe).
 */
class StudentFileController extends Controller
{
    public function photo(Student $student)
    {
        Gate::authorize('viewPhoto', $student);

        return PrivateFiles::response($student->photo);
    }

    public function document(Student $student, int $number)
    {
        Gate::authorize('viewDocuments', $student);

        $path = ((array) $student->documents)["doc_{$number}"] ?? null;

        return PrivateFiles::response($path, $path ? "document-{$number}-".$student->matricule.'.'.pathinfo($path, PATHINFO_EXTENSION) : null);
    }
}
