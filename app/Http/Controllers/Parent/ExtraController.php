<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Enrollment;
use App\Models\Extra;
use App\Models\ExtraInstallment;
use App\Models\ExtraSubscription;
use App\Models\ExtraTarif;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
            ->with(['extra.category', 'extraTarif', 'payments' => fn ($q) => $q->orderByDesc('payment_date')])
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

        if (ExtraSubscription::where('student_id', $studentId)->where('extra_id', $extra->id)
            ->where('school_year_id', $enrollment->school_year_id)->exists()) {
            return back()->withErrors(['error' => 'Une demande ou inscription existe déjà pour ce service.']);
        }

        if (! $extra->hasAvailableCapacity()) {
            return back()->withErrors(['error' => "⚠️ « {$extra->name} » est complet, votre demande ne peut pas être prise en compte pour le moment."]);
        }

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

        DB::beginTransaction();
        try {
            $periods = $extra->isRecurring()
                ? $this->defaultPeriods($tarif)
                : ['unique'];

            $totalAmount = $extra->isRecurring() ? count($periods) * $tarif->amount : $tarif->amount;

            $subscription = ExtraSubscription::create([
                'school_id' => $student->school_id,
                'student_id' => $studentId,
                'extra_id' => $extra->id,
                'extra_tarif_id' => $tarif->id,
                'school_year_id' => $enrollment->school_year_id,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'remaining_amount' => $totalAmount,
                'status' => 'requested',
                'requested_by' => $parent->id,
            ]);

            foreach ($periods as $period) {
                $dueDate = $period === 'unique'
                    ? now()
                    : Carbon::parse($period.'-01')->day(min($tarif->due_day, Carbon::parse($period.'-01')->daysInMonth));

                ExtraInstallment::create([
                    'extra_subscription_id' => $subscription->id,
                    'period' => $period,
                    'amount' => $tarif->amount,
                    'paid_amount' => 0,
                    'due_date' => $dueDate,
                    'status' => 'pending',
                ]);
            }

            DB::commit();

            ActivityLog::logAction('extras.subscription.requested', "Demande d'inscription à « {$extra->name} » pour {$student->first_name} {$student->last_name}");

            return redirect()->route('parent.extras.index', $studentId)
                ->with('success', "✅ Votre demande d'inscription à « {$extra->name} » a été envoyée à l'établissement.");
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

    /**
     * Génère la liste des périodes (Y-m) par défaut d'un tarif récurrent : celles
     * comprises entre start_period/end_period si définies, sinon periods_count mois
     * à partir du mois courant.
     */
    private function defaultPeriods(ExtraTarif $tarif): array
    {
        if ($tarif->start_period && $tarif->end_period) {
            $periods = [];
            $cursor = Carbon::parse($tarif->start_period.'-01');
            $end = Carbon::parse($tarif->end_period.'-01');
            while ($cursor->lte($end)) {
                $periods[] = $cursor->format('Y-m');
                $cursor->addMonth();
            }

            return $periods;
        }

        $count = $tarif->periods_count ?? 1;
        $periods = [];
        $cursor = now()->startOfMonth();
        for ($i = 0; $i < $count; $i++) {
            $periods[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        return $periods;
    }
}
