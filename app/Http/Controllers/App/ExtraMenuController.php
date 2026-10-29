<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Extra;
use App\Models\ExtraMenu;
use App\Models\ExtraSubscription;
use App\Support\SchoolRules;
use Illuminate\Http\Request;

/**
 * Menus des extras de restauration, saisis jour par jour.
 */
class ExtraMenuController extends Controller
{
    /**
     * Menus d'un extra pour un mois donné.
     */
    public function index(Request $request)
    {
        $schoolId = session('current_school_id');
        $extraId = $request->get('extra_id');
        $month = $request->get('month', now()->format('Y-m'));

        $extras = Extra::where('school_id', $schoolId)->orderBy('name')->get();

        $menus = collect();
        $studentsWithRestrictions = collect();
        if ($extraId) {
            $menus = ExtraMenu::where('school_id', $schoolId)
                ->where('extra_id', $extraId)
                ->whereBetween('date', [$month.'-01', date('Y-m-t', strtotime($month.'-01'))])
                ->orderBy('date')
                ->get();

            $studentsWithRestrictions = ExtraSubscription::where('school_id', $schoolId)
                ->where('extra_id', $extraId)
                ->where('status', 'active')
                ->whereHas('student', fn ($q) => $q->whereNotNull('dietary_restrictions')->where('dietary_restrictions', '!=', ''))
                ->with('student')
                ->get()
                ->pluck('student')
                ->sortBy(fn ($s) => $s->last_name.$s->first_name);
        }

        return view('app.extras.menus.index', compact('extras', 'extraId', 'month', 'menus', 'studentsWithRestrictions'));
    }

    /**
     * Enregistre (ou remplace) le menu d'un jour.
     */
    public function store(Request $request)
    {
        $schoolId = session('current_school_id');

        $validated = $request->validate([
            'extra_id' => ['required', SchoolRules::exists('extras')],
            'date' => 'required|date',
            'entree' => 'nullable|string|max:150',
            'plat' => 'nullable|string|max:150',
            'dessert' => 'nullable|string|max:150',
            'gouter' => 'nullable|string|max:150',
        ]);

        $validated['school_id'] = $schoolId;

        ExtraMenu::updateOrCreate(
            ['extra_id' => $validated['extra_id'], 'date' => $validated['date']],
            $validated
        );

        return redirect()->route('extras.menus.index', ['extra_id' => $validated['extra_id'], 'month' => substr($validated['date'], 0, 7)])
            ->with('success', '✅ Menu enregistré avec succès !');
    }

    /**
     * Supprime le menu d'un jour.
     */
    public function destroy($id)
    {
        $menu = ExtraMenu::where('school_id', session('current_school_id'))->findOrFail($id);
        $extraId = $menu->extra_id;
        $month = substr($menu->date->format('Y-m-d'), 0, 7);
        $menu->delete();

        return redirect()->route('extras.menus.index', ['extra_id' => $extraId, 'month' => $month])
            ->with('success', '✅ Menu supprimé avec succès !');
    }
}
