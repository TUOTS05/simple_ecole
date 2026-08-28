<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Extra;
use App\Models\ExtraAttendance;
use App\Models\ExtraInstallment;
use App\Models\ExtraSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ExtraAttendanceController extends Controller
{
    /**
     * Grille de présence/consommation pour un extra à une date donnée.
     */
    public function index(Request $request)
    {
        $schoolId = session('current_school_id');
        $extraId = $request->get('extra_id');
        $date = $request->get('date', now()->format('Y-m-d'));

        $extras = Extra::where('school_id', $schoolId)->orderBy('name')->get();

        $subscriptions = collect();
        if ($extraId) {
            $subscriptions = ExtraSubscription::where('school_id', $schoolId)
                ->where('extra_id', $extraId)
                ->where('status', 'active')
                ->with(['student', 'attendances' => fn ($q) => $q->where('date', $date)])
                ->get()
                ->sortBy(fn ($s) => $s->student->last_name.$s->student->first_name);
        }

        return view('app.extras.attendances.index', compact('extras', 'extraId', 'date', 'subscriptions'));
    }

    public function store(Request $request)
    {
        $schoolId = session('current_school_id');

        $validated = $request->validate([
            'extra_id' => 'required|exists:extras,id',
            'date' => 'required|date',
            'records' => 'required|array',
            'records.*.status' => 'required|in:present,absent',
            'records.*.checked_in_at' => 'nullable|date_format:H:i',
            'records.*.checked_out_at' => 'nullable|date_format:H:i',
        ]);

        foreach ($validated['records'] as $subscriptionId => $data) {
            $subscription = ExtraSubscription::where('school_id', $schoolId)->find($subscriptionId);
            if (! $subscription) {
                continue;
            }

            $attendance = ExtraAttendance::updateOrCreate(
                ['extra_subscription_id' => $subscriptionId, 'date' => $validated['date']],
                [
                    'status' => $data['status'],
                    'checked_in_at' => ! empty($data['checked_in_at']) ? $validated['date'].' '.$data['checked_in_at'] : null,
                    'checked_out_at' => ! empty($data['checked_out_at']) ? $validated['date'].' '.$data['checked_out_at'] : null,
                    'recorded_by' => auth()->id(),
                ]
            );

            $attendance->computeOverage();
            $attendance->save();
        }

        return redirect()->route('extras.attendances.index', ['extra_id' => $validated['extra_id'], 'date' => $validated['date']])
            ->with('success', '✅ Présences enregistrées avec succès !');
    }

    /**
     * QR code d'un abonnement (à imprimer sur un badge), pour pointage rapide.
     */
    public function qrcode($subscriptionId)
    {
        $subscription = ExtraSubscription::where('school_id', session('current_school_id'))->findOrFail($subscriptionId);

        $token = Crypt::encryptString('extra_subscription:'.$subscription->id);

        return response(QrCode::size(280)->generate($token))->header('Content-Type', 'image/svg+xml');
    }

    public function scanForm()
    {
        return view('app.extras.attendances.scan');
    }

    /**
     * Un même scan le matin fait un check-in, un second scan le même jour fait un check-out
     * (utile pour la garderie). Fonctionne avec un lecteur QR USB (émule un clavier) ou une
     * valeur collée manuellement.
     */
    public function scanStore(Request $request)
    {
        $validated = $request->validate(['code' => 'required|string']);

        try {
            $decoded = Crypt::decryptString($validated['code']);
        } catch (\Exception $e) {
            return back()->withErrors(['code' => 'Code QR invalide ou illisible.']);
        }

        if (! str_starts_with($decoded, 'extra_subscription:')) {
            return back()->withErrors(['code' => 'Code QR invalide.']);
        }

        $subscriptionId = (int) str_replace('extra_subscription:', '', $decoded);
        $subscription = ExtraSubscription::where('school_id', session('current_school_id'))
            ->where('status', 'active')
            ->with('student', 'extra')
            ->find($subscriptionId);

        if (! $subscription) {
            return back()->withErrors(['code' => 'Abonnement introuvable ou inactif.']);
        }

        $today = now()->format('Y-m-d');
        $attendance = ExtraAttendance::firstOrNew(['extra_subscription_id' => $subscription->id, 'date' => $today]);

        if (! $attendance->exists) {
            $attendance->fill(['status' => 'present', 'checked_in_at' => now(), 'recorded_by' => auth()->id()]);
            $attendance->save();
            $message = "✅ Arrivée pointée pour {$subscription->student->first_name} {$subscription->student->last_name} — {$subscription->extra->name}";
        } elseif (! $attendance->checked_out_at) {
            $attendance->checked_out_at = now();
            $attendance->computeOverage();
            $attendance->save();
            $message = "✅ Départ pointé pour {$subscription->student->first_name} {$subscription->student->last_name} — {$subscription->extra->name}";
        } else {
            $message = 'ℹ️ Ce badge a déjà été pointé (arrivée et départ) aujourd\'hui.';
        }

        return back()->with('success', $message);
    }

    /**
     * Transforme le dépassement horaire calculé sur une présence en une échéance
     * ad-hoc facturée sur l'abonnement (garderie).
     */
    public function billOverage($attendanceId)
    {
        $attendance = ExtraAttendance::whereHas('subscription', fn ($q) => $q->where('school_id', session('current_school_id')))
            ->findOrFail($attendanceId);

        if (! $attendance->overage_amount || $attendance->overage_amount <= 0) {
            return back()->withErrors(['error' => 'Aucun dépassement à facturer pour cette présence.']);
        }

        if ($attendance->overage_billed_at) {
            return back()->withErrors(['error' => 'Ce dépassement a déjà été facturé.']);
        }

        $subscription = $attendance->subscription;

        ExtraInstallment::create([
            'extra_subscription_id' => $subscription->id,
            'period' => 'depassement-'.$attendance->date->format('Y-m-d'),
            'amount' => $attendance->overage_amount,
            'paid_amount' => 0,
            'due_date' => now(),
            'status' => 'pending',
        ]);

        $subscription->total_amount += $attendance->overage_amount;
        $subscription->save();
        $subscription->recalculateAmounts();

        $attendance->overage_billed_at = now();
        $attendance->save();

        return back()->with('success', '✅ Dépassement facturé avec succès ('.number_format($attendance->overage_amount, 0, ',', ' ').' FCFA).');
    }
}
