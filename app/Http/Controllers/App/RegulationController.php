<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\SchoolRegulation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Règlement intérieur de l'école : rédaction/publication de versions successives et suivi
 * des acceptations par les parents (voir Parent\RegulationController pour l'autre côté).
 */
class RegulationController extends Controller
{
    /**
     * Versions du règlement intérieur, avec leur nombre d'acceptations.
     */
    public function index()
    {
        $schoolId = session('current_school_id');

        $versions = SchoolRegulation::where('school_id', $schoolId)
            ->withCount('acceptances')
            ->with('publisher')
            ->orderByDesc('version')
            ->get();

        return view('app.regulations.index', [
            'current' => $versions->first(),
            'versions' => $versions,
            'parentsCount' => User::where('school_id', $schoolId)->where('role', 'parent')->count(),
        ]);
    }

    /**
     * Publie une nouvelle version du règlement (texte et/ou PDF) ; les parents devront l'accepter à
     * nouveau.
     */
    public function store(Request $request)
    {
        $schoolId = session('current_school_id');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string|max:100000|required_without:file',
            'file' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $regulation = new SchoolRegulation([
            'title' => $validated['title'],
            'content' => $validated['content'] ?? null,
            'requires_acceptance' => $request->boolean('requires_acceptance'),
        ]);
        $regulation->school_id = $schoolId;
        $regulation->version = (SchoolRegulation::where('school_id', $schoolId)->max('version') ?? 0) + 1;
        $regulation->published_by = auth()->id();

        if ($request->hasFile('file')) {
            // Disque privé : le fichier n'est servi qu'au travers de download(), après contrôle d'école
            $regulation->file_path = $request->file('file')->store("regulations/{$schoolId}", 'local');
        }

        $regulation->save();

        return redirect()->route('app.regulations.index')
            ->with('success', "Version {$regulation->version} du règlement publiée.");
    }

    /**
     * Suivi des acceptations d'une version : parents ayant accepté / restant à accepter.
     */
    public function show(Request $request, SchoolRegulation $regulation)
    {
        $this->authorizeSchool($regulation);

        $acceptedAt = $regulation->acceptances()->pluck('accepted_at', 'user_id');

        $parents = User::where('school_id', $regulation->school_id)
            ->where('role', 'parent')
            ->when($request->status === 'accepted', fn ($q) => $q->whereIn('id', $acceptedAt->keys()))
            ->when($request->status === 'pending', fn ($q) => $q->whereNotIn('id', $acceptedAt->keys()))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(25)
            ->appends($request->query());

        return view('app.regulations.show', compact('regulation', 'parents', 'acceptedAt'));
    }

    /**
     * Télécharge le PDF d'une version du règlement.
     */
    public function download(SchoolRegulation $regulation)
    {
        $this->authorizeSchool($regulation);
        abort_unless($regulation->file_path && Storage::disk('local')->exists($regulation->file_path), 404);

        return Storage::disk('local')->download($regulation->file_path, "reglement_interieur_v{$regulation->version}.pdf");
    }

    private function authorizeSchool(SchoolRegulation $regulation): void
    {
        abort_if($regulation->school_id !== session('current_school_id'), 403);
    }
}
