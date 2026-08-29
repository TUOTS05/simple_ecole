<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExtraSubscription;
use App\Models\Student;
use Illuminate\Http\Request;

/**
 * API parent en lecture : enfants, abonnements aux extras, échéances,
 * paiements, et position du bus.
 *
 * Aucune écriture : souscrire, payer ou demander une suspension reste
 * sur le site web (le paiement en ligne passe par une redirection
 * CinetPay, difficilement pilotable depuis une app tierce).
 *
 * Chaque route repasse par childOrFail() : un parent ne peut jamais
 * lire les données d'un élève qui n'est pas le sien, même en devinant
 * un identifiant.
 */
class ParentExtraController extends Controller
{
    public function children(Request $request)
    {
        $children = $request->user()->children()->with('school')->get();

        return response()->json([
            'data' => $children->map(fn ($student) => [
                'id' => $student->id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'matricule' => $student->matricule,
                'school' => $student->school ? [
                    'id' => $student->school->id,
                    'name' => $student->school->name,
                ] : null,
            ]),
        ]);
    }

    public function subscriptions(Request $request, $studentId)
    {
        $student = $this->childOrFail($request, $studentId);

        $subscriptions = ExtraSubscription::where('student_id', $student->id)
            ->whereHas('schoolYear', fn ($q) => $q->where('school_id', $student->school_id)->where('is_active', true))
            ->with(['extra.category', 'extraTarif', 'transportAssignment.vehicle'])
            ->get();

        return response()->json([
            'data' => $subscriptions->map(fn ($sub) => [
                'id' => $sub->id,
                'status' => $sub->status,
                'extra' => [
                    'id' => $sub->extra->id,
                    'name' => $sub->extra->name,
                    'category' => $sub->extra->category->name ?? null,
                    'billing_type' => $sub->extra->billing_type,
                ],
                'total_amount' => (float) $sub->total_amount,
                'paid_amount' => (float) $sub->paid_amount,
                'remaining_amount' => (float) $sub->remaining_amount,
                'monthly_amount' => $sub->extraTarif ? (float) $sub->extraTarif->amount : null,
                'has_discount' => $sub->hasDiscount(),
                // Le suivi GPS n'est proposé que sur un abonnement actif,
                // comme côté web.
                'bus_tracking_available' => $sub->status === 'active'
                    && $sub->transportAssignment
                    && $sub->transportAssignment->vehicle !== null,
            ]),
        ]);
    }

    public function installments(Request $request, $studentId, $subscriptionId)
    {
        $subscription = $this->subscriptionOrFail($request, $studentId, $subscriptionId);

        $installments = $subscription->installments()->orderBy('due_date')->get();

        return response()->json([
            'data' => $installments->map(fn ($i) => [
                'id' => $i->id,
                'period' => $i->period,
                'period_label' => $i->period_label,
                'amount' => (float) $i->amount,
                'paid_amount' => (float) $i->paid_amount,
                'remaining_amount' => (float) $i->remaining_amount,
                'due_date' => $i->due_date?->toDateString(),
                'status' => $i->status,
                'is_overdue' => $i->is_overdue,
            ]),
        ]);
    }

    public function payments(Request $request, $studentId, $subscriptionId)
    {
        $subscription = $this->subscriptionOrFail($request, $studentId, $subscriptionId);

        $payments = $subscription->payments()->orderByDesc('payment_date')->get();

        return response()->json([
            'data' => $payments->map(fn ($p) => [
                'id' => $p->id,
                'amount' => (float) $p->amount,
                'payment_method' => $p->payment_method,
                'payment_date' => $p->payment_date?->toDateString(),
                'reference' => $p->reference,
            ]),
        ]);
    }

    /**
     * Dernière position connue du bus de l'élève. Même règle que côté
     * web : abonnement actif uniquement, et position marquée obsolète
     * au-delà de 10 minutes.
     */
    public function busPosition(Request $request, $studentId, $subscriptionId)
    {
        $subscription = $this->subscriptionOrFail($request, $studentId, $subscriptionId, activeOnly: true);
        $subscription->loadMissing('transportAssignment.vehicle', 'transportAssignment.route', 'transportAssignment.stop');

        $vehicle = $subscription->transportAssignment->vehicle ?? null;

        if (! $vehicle) {
            return response()->json(['message' => 'Aucun véhicule affecté.'], 404);
        }

        if (! $vehicle->last_latitude) {
            return response()->json(['available' => false]);
        }

        return response()->json([
            'available' => true,
            'latitude' => (float) $vehicle->last_latitude,
            'longitude' => (float) $vehicle->last_longitude,
            'last_location_at' => $vehicle->last_location_at?->toIso8601String(),
            'stale' => $vehicle->hasStaleLocation(),
            'vehicle' => [
                'plate_number' => $vehicle->plate_number,
                'driver_name' => $vehicle->driver_name,
            ],
            'route' => $subscription->transportAssignment->route->name ?? null,
            'stop' => $subscription->transportAssignment->stop->label ?? null,
        ]);
    }

    private function childOrFail(Request $request, $studentId): Student
    {
        return $request->user()->children()->where('students.id', $studentId)->firstOrFail();
    }

    private function subscriptionOrFail(
        Request $request,
        $studentId,
        $subscriptionId,
        bool $activeOnly = false
    ): ExtraSubscription {
        $student = $this->childOrFail($request, $studentId);

        return ExtraSubscription::where('student_id', $student->id)
            ->when($activeOnly, fn ($q) => $q->where('status', 'active'))
            ->findOrFail($subscriptionId);
    }
}
