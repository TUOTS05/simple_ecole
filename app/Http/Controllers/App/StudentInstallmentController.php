<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\StudentInstallment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Échéances de paiement ajoutées ou retirées à la main sur une inscription.
 */
class StudentInstallmentController extends Controller
{
    /**
     * Ajoute une échéance à une inscription de l'école.
     */
    public function store(Request $request): RedirectResponse
    {
        $schoolId = (int) $request->user()->school_id;
        $data = $request->validate([
            'enrollment_id' => [
                'required',
                Rule::exists('enrollments', 'id')->where('school_id', $schoolId),
            ],
            'type' => ['required', Rule::in(['registration', 'installment'])],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'due_date' => ['required', 'date'],
        ]);

        StudentInstallment::create([
            ...$data,
            'school_id' => $schoolId,
            'paid_amount' => 0,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Échéance ajoutée avec succès.');
    }

    /**
     * Supprime une échéance, refusé si elle a déjà été réglée.
     */
    public function destroy(Request $request, StudentInstallment $installment): RedirectResponse
    {
        abort_unless($installment->school_id === (int) $request->user()->school_id, 403);
        abort_if($installment->paid_amount > 0, 422, 'Une échéance déjà réglée ne peut pas être supprimée.');

        $installment->delete();

        return back()->with('success', 'Échéance supprimée avec succès.');
    }
}
