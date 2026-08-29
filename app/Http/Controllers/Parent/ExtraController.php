<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Enrollment;
use App\Models\Extra;
use App\Models\ExtraOnlinePayment;
use App\Models\ExtraSubscription;
use App\Models\ExtraTarif;
use App\Services\CinetPayService;
use App\Services\ExtraOnlinePaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExtraController extends Controller
{
    /**
     * « Mes extras » : liste des services souscrits pour un enfant.
     */
    public function index($studentId)
    {
        $parent = auth()->user();

        $student = $parent->children()->where('students.id', $studentId)->with('school')->firstOrFail();
        $siblings = $parent->children()->get();

        $subscriptions = ExtraSubscription::where('student_id', $studentId)
            ->whereHas('schoolYear', fn ($q) => $q->where('school_id', $student->school_id)->where('is_active', true))
            ->with(['extra.category', 'extraTarif', 'payments' => fn ($q) => $q->orderByDesc('payment_date'), 'transportAssignment.vehicle'])
            ->get();

        $monthlyTotal = $subscriptions->where('status', 'active')->sum(fn ($s) => $s->extraTarif->amount ?? 0);

        return view('parent.extras.index', compact('student', 'siblings', 'subscriptions', 'monthlyTotal'));
    }

    /**
     * Catalogue des extras disponibles pour un enfant (pas encore souscrits).
     */
    public function catalogue($studentId)
    {
        $parent = auth()->user();

        $student = $parent->children()->where('students.id', $studentId)->with('school')->firstOrFail();
        $siblings = $parent->children()->get();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->whereHas('schoolYear', fn ($q) => $q->where('school_id', $student->school_id)->where('is_active', true))
            ->first();

        $alreadySubscribedIds = ExtraSubscription::where('student_id', $studentId)
            ->when($enrollment, fn ($q) => $q->where('school_year_id', $enrollment->school_year_id))
            ->whereNotIn('status', ['terminated'])
            ->pluck('extra_id');

        $extras = Extra::where('school_id', $student->school_id)
            ->where('status', 'active')
            ->whereNotIn('id', $alreadySubscribedIds)
            ->with('category')
            ->orderBy('name')
            ->get()
            ->map(function ($extra) use ($enrollment) {
                $tarif = null;
                if ($enrollment) {
                    $tarif = ExtraTarif::where('extra_id', $extra->id)
                        ->where('school_year_id', $enrollment->school_year_id)
                        ->where('school_class_id', $enrollment->school_class_id)
                        ->first()
                        ?? ExtraTarif::where('extra_id', $extra->id)
                            ->where('school_year_id', $enrollment->school_year_id)
                            ->whereNull('school_class_id')
                            ->first();
                }
                $extra->applicable_tarif = $tarif;
                $extra->seats_left = $extra->capacity !== null ? max(0, $extra->capacity - $extra->occupiedSeatsCount()) : null;

                return $extra;
            });

        return view('parent.extras.catalogue', compact('student', 'siblings', 'extras', 'enrollment'));
    }

    /**
     * Demande d'inscription d'un enfant à un extra (statut "requested", à valider par l'administration).
     */
    public function request($studentId, $extraId)
    {
        $parent = auth()->user();

        $student = $parent->children()->where('students.id', $studentId)->firstOrFail();
        $extra = Extra::where('school_id', $student->school_id)->where('status', 'active')->findOrFail($extraId);

        $enrollment = Enrollment::where('student_id', $studentId)
            ->whereHas('schoolYear', fn ($q) => $q->where('school_id', $student->school_id)->where('is_active', true))
            ->first();

        if (! $enrollment) {
            return back()->withErrors(['error' => "Votre enfant n'a pas d'inscription active cette année."]);
        }

        if (! $extra->isRegistrationOpen()) {
            return back()->withErrors(['error' => "La date limite d'inscription à « {$extra->name} » est dépassée."]);
        }

        if (ExtraSubscription::where('student_id', $studentId)->where('extra_id', $extra->id)
            ->where('school_year_id', $enrollment->school_year_id)->exists()) {
            return back()->withErrors(['error' => 'Une demande ou inscription existe déjà pour ce service.']);
        }

        $hasCapacity = $extra->hasAvailableCapacity();

        $tarif = ExtraTarif::where('extra_id', $extra->id)
            ->where('school_year_id', $enrollment->school_year_id)
            ->where('school_class_id', $enrollment->school_class_id)
            ->first()
            ?? ExtraTarif::where('extra_id', $extra->id)
                ->where('school_year_id', $enrollment->school_year_id)
                ->whereNull('school_class_id')
                ->first();

        if (! $tarif) {
            return back()->withErrors(['error' => 'Aucun tarif n\'est encore défini pour ce service, contactez l\'établissement.']);
        }

        $periods = $extra->isRecurring() ? $tarif->defaultPeriods() : ['unique'];
        $totalAmount = $extra->isRecurring() ? count($periods) * $tarif->amount : $tarif->amount;

        DB::beginTransaction();
        try {
            $subscription = ExtraSubscription::create([
                'school_id' => $student->school_id,
                'student_id' => $studentId,
                'extra_id' => $extra->id,
                'extra_tarif_id' => $tarif->id,
                'school_year_id' => $enrollment->school_year_id,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'remaining_amount' => $totalAmount,
                'status' => $hasCapacity ? 'requested' : 'waitlisted',
                'requested_by' => $parent->id,
            ]);

            // Les échéances ne sont créées qu'une fois la place effectivement acquise
            // (demande directe si capacité dispo) : une inscription en liste d'attente
            // n'a pas encore de dette envers l'établissement.
            if ($hasCapacity) {
                $tarif->createDefaultInstallmentsFor($subscription);
            }

            DB::commit();

            ActivityLog::logAction(
                'extras.subscription.requested',
                ($hasCapacity ? "Demande d'inscription à" : "Demande d'inscription (liste d'attente) à")." « {$extra->name} » pour {$student->first_name} {$student->last_name}"
            );

            $message = $hasCapacity
                ? "✅ Votre demande d'inscription à « {$extra->name} » a été envoyée à l'établissement."
                : "🕒 « {$extra->name} » est complet : votre enfant a été placé sur liste d'attente, vous serez prévenu(e) dès qu'une place se libère.";

            return redirect()->route('parent.extras.index', $studentId)->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Erreur : '.$e->getMessage()]);
        }
    }

    /**
     * Demande de suspension d'un service actif.
     */
    public function suspend($studentId, $subscriptionId)
    {
        $parent = auth()->user();
        $student = $parent->children()->where('students.id', $studentId)->firstOrFail();

        $subscription = ExtraSubscription::where('student_id', $student->id)
            ->where('status', 'active')
            ->findOrFail($subscriptionId);

        $subscription->update(['status' => 'suspended']);

        ActivityLog::logAction('extras.subscription.suspended', "Suspension demandée par le parent pour « {$subscription->extra->name} » ({$student->first_name} {$student->last_name})");

        return back()->with('success', '✅ Le service a été suspendu. Contactez l\'établissement pour le réactiver.');
    }

    public function downloadReceipt($studentId, $subscriptionId, $paymentId)
    {
        $parent = auth()->user();
        $student = $parent->children()->where('students.id', $studentId)->firstOrFail();

        $subscription = ExtraSubscription::where('id', $subscriptionId)->where('student_id', $student->id)->firstOrFail();
        $payment = $subscription->payments()->where('id', $paymentId)->firstOrFail();

        $payment->load(['subscription.student', 'subscription.extra', 'subscription.schoolYear', 'receivedByUser']);

        $pdf = Pdf::loadView('pdf.extra-receipt', [
            'payment' => $payment,
            'subscription' => $subscription,
            'student' => $student,
            'extra' => $subscription->extra,
            'school' => $student->school,
        ]);

        $filename = 'Recu_Extra_'.str_pad($payment->id, 6, '0', STR_PAD_LEFT).'.pdf';

        return $pdf->download($filename);
    }

    // ==========================================
    // PAIEMENT EN LIGNE (CinetPay)
    // ==========================================

    /**
     * Initie un paiement en ligne pour un abonnement actif et redirige le
     * parent vers la page de paiement CinetPay (ou vers la simulation locale
     * tant que CINETPAY_DEV_MODE=true).
     */
    public function payOnline(Request $request, CinetPayService $cinetPay, $studentId, $subscriptionId)
    {
        $parent = auth()->user();
        $student = $parent->children()->where('students.id', $studentId)->firstOrFail();

        $subscription = ExtraSubscription::where('student_id', $studentId)
            ->where('status', 'active')
            ->with('extra')
            ->findOrFail($subscriptionId);

        if ($subscription->remaining_amount <= 0) {
            return back()->withErrors(['error' => 'Ce service est déjà entièrement soldé.']);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:'.$subscription->remaining_amount,
        ]);

        $transactionId = 'EXTRA-'.$subscription->id.'-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));

        $onlinePayment = ExtraOnlinePayment::create([
            'school_id' => $student->school_id,
            'extra_subscription_id' => $subscription->id,
            'transaction_id' => $transactionId,
            'amount' => $validated['amount'],
            'status' => 'pending',
            'initiated_by' => $parent->id,
        ]);

        $result = $cinetPay->initiatePayment([
            'transaction_id' => $transactionId,
            'amount' => (int) round($validated['amount']),
            'description' => 'Paiement '.$subscription->extra->name.' - '.$student->first_name.' '.$student->last_name,
            'customer_name' => $parent->last_name ?? '',
            'customer_surname' => $parent->first_name ?? '',
            'customer_email' => $parent->email,
            'customer_phone_number' => $parent->phone,
            'notify_url' => route('webhooks.cinetpay.extras'),
            'return_url' => route('parent.extras.pay-online.return', ['student' => $studentId, 'transaction' => $transactionId]),
        ]);

        if (! ($result['success'] ?? false)) {
            $onlinePayment->update(['status' => 'failed', 'gateway_response' => json_encode($result)]);

            return back()->withErrors(['error' => "Impossible d'initier le paiement en ligne : ".($result['error'] ?? 'erreur inconnue')]);
        }

        $onlinePayment->update(['payment_token' => $result['payment_token'] ?? null]);

        if ($cinetPay->isDevMode()) {
            return redirect()->route('parent.extras.pay-online.simulate', ['student' => $studentId, 'transaction' => $transactionId]);
        }

        return redirect()->away($result['payment_url']);
    }

    /**
     * Point de retour navigateur après paiement (succès, échec ou abandon côté
     * CinetPay). Revérifie le statut tout de suite au cas où le webhook ne
     * serait pas encore arrivé, pour un retour instantané à l'utilisateur.
     */
    public function payOnlineReturn(ExtraOnlinePaymentService $service, $studentId, $transaction)
    {
        $parent = auth()->user();
        $parent->children()->where('students.id', $studentId)->firstOrFail();

        ExtraOnlinePayment::where('transaction_id', $transaction)
            ->whereHas('subscription', fn ($q) => $q->where('student_id', $studentId))
            ->firstOrFail();

        $onlinePayment = $service->confirmFromGateway($transaction);

        $message = match ($onlinePayment->status) {
            'completed' => '✅ Paiement confirmé avec succès !',
            'failed' => '❌ Le paiement a échoué ou a été annulé.',
            default => '⏳ Paiement en cours de vérification, actualisez cette page dans un instant.',
        };

        return redirect()->route('parent.extras.index', $studentId)->with('success', $message);
    }

    /**
     * Page de paiement factice, affichée uniquement quand CINETPAY_DEV_MODE=true
     * (aucune clé API configurée) : remplace la page hébergée CinetPay pour
     * pouvoir tester tout le flux sans compte marchand ni argent réel.
     */
    public function payOnlineSimulate($studentId, $transaction)
    {
        $parent = auth()->user();
        $student = $parent->children()->where('students.id', $studentId)->firstOrFail();

        $onlinePayment = ExtraOnlinePayment::where('transaction_id', $transaction)
            ->whereHas('subscription', fn ($q) => $q->where('student_id', $studentId))
            ->with('subscription.extra')
            ->firstOrFail();

        if ($onlinePayment->status !== 'pending') {
            return redirect()->route('parent.extras.index', $studentId);
        }

        return view('parent.extras.pay-online-simulate', compact('student', 'onlinePayment'));
    }

    public function payOnlineSimulateConfirm(Request $request, ExtraOnlinePaymentService $service, $studentId, $transaction)
    {
        $parent = auth()->user();
        $parent->children()->where('students.id', $studentId)->firstOrFail();

        ExtraOnlinePayment::where('transaction_id', $transaction)
            ->whereHas('subscription', fn ($q) => $q->where('student_id', $studentId))
            ->firstOrFail();

        $accepted = $request->input('decision') === 'success';
        $onlinePayment = $service->confirmFromSimulation($transaction, $accepted);

        $message = $onlinePayment->status === 'completed'
            ? '✅ Paiement simulé confirmé avec succès !'
            : '❌ Paiement simulé refusé/annulé.';

        return redirect()->route('parent.extras.index', $studentId)->with('success', $message);
    }

    // ==========================================
    // SUIVI GPS DU BUS
    // ==========================================

    public function trackBus($studentId, $subscriptionId)
    {
        $parent = auth()->user();
        $student = $parent->children()->where('students.id', $studentId)->firstOrFail();

        // Seul un abonnement actif donne accès à la position du bus : un abonnement
        // suspendu ou résilié ne doit plus permettre de suivre le véhicule.
        $subscription = ExtraSubscription::where('student_id', $studentId)
            ->where('status', 'active')
            ->with('extra', 'transportAssignment.vehicle', 'transportAssignment.route', 'transportAssignment.stop')
            ->findOrFail($subscriptionId);

        if (! $subscription->transportAssignment || ! $subscription->transportAssignment->vehicle) {
            abort(404);
        }

        return view('parent.extras.track-bus', compact('student', 'subscription'));
    }

    public function trackBusData($studentId, $subscriptionId)
    {
        $parent = auth()->user();
        $parent->children()->where('students.id', $studentId)->firstOrFail();

        $subscription = ExtraSubscription::where('student_id', $studentId)
            ->where('status', 'active')
            ->with('transportAssignment.vehicle')
            ->findOrFail($subscriptionId);

        $vehicle = $subscription->transportAssignment->vehicle ?? null;

        if (! $vehicle || ! $vehicle->last_latitude) {
            return response()->json(['available' => false]);
        }

        return response()->json([
            'available' => true,
            'latitude' => (float) $vehicle->last_latitude,
            'longitude' => (float) $vehicle->last_longitude,
            'last_location_at' => $vehicle->last_location_at?->format('H:i:s'),
            'stale' => $vehicle->hasStaleLocation(),
        ]);
    }
}
