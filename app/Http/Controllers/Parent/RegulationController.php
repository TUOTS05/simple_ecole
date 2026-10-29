<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\SchoolRegulation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Consultation et acceptation, par le parent, de la version en vigueur du règlement
 * intérieur de son école.
 */
class RegulationController extends Controller
{
    public function show()
    {
        $parent = auth()->user();
        $regulation = SchoolRegulation::currentFor($parent->school_id);

        return view('parent.regulation', [
            'regulation' => $regulation,
            'acceptance' => $regulation?->acceptances()->where('user_id', $parent->id)->first(),
        ]);
    }

    public function accept(Request $request)
    {
        $parent = auth()->user();
        $regulation = SchoolRegulation::currentFor($parent->school_id);
        abort_unless($regulation, 404);

        // La version acceptée est celle affichée au parent : si l'école a publié entre-temps,
        // on le renvoie lire la nouvelle plutôt que d'enregistrer une acceptation à l'aveugle.
        if ((int) $request->input('version') !== $regulation->version) {
            return redirect()->route('parent.regulation.show')
                ->with('error', 'Le règlement vient d\'être mis à jour : merci de prendre connaissance de la nouvelle version.');
        }

        $regulation->acceptances()->firstOrCreate(
            ['user_id' => $parent->id],
            ['ip_address' => $request->ip(), 'accepted_at' => now()],
        );

        return redirect()->route('parent.regulation.show')
            ->with('success', 'Merci, votre acceptation du règlement intérieur a bien été enregistrée.');
    }

    public function download()
    {
        $regulation = SchoolRegulation::currentFor(auth()->user()->school_id);
        abort_unless($regulation?->file_path && Storage::disk('local')->exists($regulation->file_path), 404);

        return Storage::disk('local')->download($regulation->file_path, "reglement_interieur_v{$regulation->version}.pdf");
    }
}
