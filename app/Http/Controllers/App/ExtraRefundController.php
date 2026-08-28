<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Extra;
use App\Models\ExtraRefund;
use App\Models\ExtraSubscription;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class ExtraRefundController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));
        $extraId = $request->get('extra_id', '');

        $refundableSubscriptions = ExtraSubscription::where('school_id', $schoolId)
            ->where('school_year_id', $schoolYearId)
            ->where('paid_amount', '>', 0)
            ->whereIn('status', ['active', 'suspended'])
            ->with(['student', 'extra'])
            ->get();

        $refundsQuery = ExtraRefund::where('school_id', $schoolId)
            ->whereHas('subscription', fn ($q) => $q->where('school_year_id', $schoolYearId))
            ->with(['subscription.student', 'subscription.extra', 'processedBy'])
            ->orderByDesc('processed_at');

        if ($extraId) {
            $refundsQuery->whereHas('subscription', fn ($q) => $q->where('extra_id', $extraId));
        }

        $refunds = $refundsQuery->paginate(20)->withQueryString();

        $extras = Extra::where('school_id', $schoolId)->orderBy('name')->get();
        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();

        return view('app.extras.refunds.index', compact(
            'refundableSubscriptions', 'refunds', 'extras', 'schoolYears', 'schoolYearId', 'extraId'
        ));
    }

    /**
     * Montant remboursable suggéré pour un abonnement (AJAX, pré-remplissage du formulaire).
     */
    public function suggested($subscriptionId)
    {
        $subscription = ExtraSubscription::where('school_id', session('current_school_id'))->findOrFail($subscriptionId);

        return response()->json(['suggested_amount' => $subscription->suggestedRefundAmount()]);
    }

    public function store(Request $request)
    {
        $schoolId = session('current_school_id');

        $validated = $request->validate([
            'extra_subscription_id' => 'required|exists:extra_subscriptions,id',
            'amount' => 'required|numeric|min:1',
            'refund_method' => 'required|in:cash,mobile_money,transfer,check,credit_note',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'terminate_subscription' => 'nullable|boolean',
        ]);

        $subscription = ExtraSubscription::where('school_id', $schoolId)->findOrFail($validated['extra_subscription_id']);

        if ($validated['amount'] > $subscription->paid_amount) {
            return back()->withErrors(['amount' => 'Le montant du remboursement dépasse le total déjà payé ('.number_format($subscription->paid_amount, 0, ',', ' ').' FCFA).'])->withInput();
        }

        ExtraRefund::create([
            'school_id' => $schoolId,
            'extra_subscription_id' => $subscription->id,
            'amount' => $validated['amount'],
            'reason' => $validated['reason'] ?? null,
            'refund_method' => $validated['refund_method'],
            'processed_by' => auth()->id(),
            'processed_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($request->boolean('terminate_subscription')) {
            $subscription->status = 'terminated';
            $subscription->end_date = now();
            $subscription->save();
        }

        ActivityLog::logAction(
            'extras.refund.created',
            'Remboursement de '.number_format($validated['amount'], 0, ',', ' ')." FCFA pour « {$subscription->extra->name} » (élève : {$subscription->student->first_name} {$subscription->student->last_name})"
        );

        return redirect()->route('extras.refunds.index', ['school_year_id' => $subscription->school_year_id])
            ->with('success', '✅ Remboursement enregistré avec succès !');
    }
}
