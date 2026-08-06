<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\CanteenInstallment;
use App\Models\CanteenPayment;
use App\Models\CanteenRate;
use App\Models\CanteenSubscription;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CanteenController extends Controller
{
    // ==========================================
    // GESTION DES TARIFS
    // ==========================================

    public function ratesIndex(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));

        $rates = CanteenRate::where('school_id', $schoolId)
            ->where('school_year_id', $schoolYearId)
            ->with('schoolClass')
            ->orderBy('school_classes.name')
            ->get();

        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();

        return view('app.canteen.rates.index', compact('rates', 'schoolYears', 'schoolYearId'));
    }

    public function ratesCreate(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));

        $classes = SchoolClass::where('school_id', $schoolId)->orderBy('name')->get();
        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();

        return view('app.canteen.rates.create', compact('classes', 'schoolYears', 'schoolYearId'));
    }

    public function ratesStore(Request $request)
    {
        $schoolId = session('current_school_id');

        $validated = $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'school_class_id' => 'required|exists:school_classes,id',
            'monthly_rate' => 'required|numeric|min:0',
            'months_count' => 'required|integer|min:1|max:12',
            'start_month' => 'required|date_format:Y-m',
            'end_month' => 'required|date_format:Y-m|after_or_equal:start_month',
            'description' => 'nullable|string|max:500',
        ]);

        $validated['school_id'] = $schoolId;

        CanteenRate::create($validated);

        return redirect()->route('app.canteen.rates.index', ['school_year_id' => $validated['school_year_id']])
            ->with('success', '✅ Tarif de cantine créé avec succès !');
    }

    public function ratesEdit($id)
    {
        $rate = CanteenRate::findOrFail($id);
        $classes = SchoolClass::where('school_id', $rate->school_id)->orderBy('name')->get();
        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();

        return view('app.canteen.rates.edit', compact('rate', 'classes', 'schoolYears'));
    }

    public function ratesUpdate(Request $request, $id)
    {
        $rate = CanteenRate::findOrFail($id);

        $validated = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'monthly_rate' => 'required|numeric|min:0',
            'months_count' => 'required|integer|min:1|max:12',
            'start_month' => 'required|date_format:Y-m',
            'end_month' => 'required|date_format:Y-m|after_or_equal:start_month',
            'description' => 'nullable|string|max:500',
        ]);

        $rate->update($validated);

        return redirect()->route('app.canteen.rates.index', ['school_year_id' => $rate->school_year_id])
            ->with('success', '✅ Tarif mis à jour avec succès !');
    }

    public function ratesDestroy($id)
    {
        $rate = CanteenRate::findOrFail($id);
        $schoolYearId = $rate->school_year_id;
        $rate->delete();

        return redirect()->route('app.canteen.rates.index', ['school_year_id' => $schoolYearId])
            ->with('success', '✅ Tarif supprimé avec succès !');
    }

    // ==========================================
    // INSCRIPTIONS DES ÉLÈVES
    // ==========================================

    public function subscriptionsIndex(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));
        $classId = $request->get('class_id');

        $query = CanteenSubscription::where('school_id', $schoolId)
            ->where('school_year_id', $schoolYearId)
            ->with(['student', 'canteenRate.schoolClass']);

        if ($classId) {
            $query->whereHas('canteenRate', fn($q) => $q->where('school_class_id', $classId));
        }

        $subscriptions = $query->get();
        $classes = SchoolClass::where('school_id', $schoolId)->orderBy('name')->get();
        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();

        // Pour le formulaire d'inscription
        $rates = CanteenRate::where('school_id', $schoolId)
            ->where('school_year_id', $schoolYearId)
            ->with('schoolClass')
            ->get();

        return view('app.canteen.subscriptions.index', compact(
            'subscriptions', 'classes', 'schoolYears', 'schoolYearId', 'classId', 'rates'
        ));
    }

    public function subscriptionsStore(Request $request)
    {
        $schoolId = session('current_school_id');

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'canteen_rate_id' => 'required|exists:canteen_rates,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $rate = CanteenRate::findOrFail($validated['canteen_rate_id']);

        // Vérifier que l'élève n'est pas déjà inscrit
        $existing = CanteenSubscription::where('student_id', $validated['student_id'])
            ->where('school_year_id', $rate->school_year_id)
            ->first();

        if ($existing) {
            return back()->withErrors(['student_id' => 'Cet élève est déjà inscrit à la cantine pour cette année.']);
        }

        DB::beginTransaction();
        try {
            $totalAmount = $rate->monthly_rate * $rate->months_count;

            $subscription = CanteenSubscription::create([
                'school_id' => $schoolId,
                'student_id' => $validated['student_id'],
                'school_year_id' => $rate->school_year_id,
                'canteen_rate_id' => $rate->id,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'remaining_amount' => $totalAmount,
                'status' => 'active',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Générer automatiquement les échéances mensuelles
            $this->generateInstallments($subscription, $rate);

            DB::commit();

            return redirect()->route('app.canteen.subscriptions.index', ['school_year_id' => $rate->school_year_id])
                ->with('success', '✅ Élève inscrit à la cantine avec succès ! ' . $rate->months_count . ' échéances générées.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur : ' . $e->getMessage()]);
        }
    }

    public function subscriptionsDestroy($id)
    {
        $subscription = CanteenSubscription::findOrFail($id);
        $schoolYearId = $subscription->school_year_id;
        $subscription->delete();

        return redirect()->route('app.canteen.subscriptions.index', ['school_year_id' => $schoolYearId])
            ->with('success', '✅ Inscription annulée avec succès !');
    }

    // ==========================================
    // PAIEMENTS
    // ==========================================

    public function paymentsIndex(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));

        $payments = CanteenPayment::where('school_id', $schoolId)
            ->whereHas('subscription', fn($q) => $q->where('school_year_id', $schoolYearId))
            ->with(['subscription.student', 'installment', 'receivedByUser'])
            ->orderBy('payment_date', 'desc')
            ->paginate(20);

        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();

        // Pour le formulaire de paiement
        $subscriptions = CanteenSubscription::where('school_id', $schoolId)
            ->where('school_year_id', $schoolYearId)
            ->where('status', 'active')
            ->with(['student', 'canteenRate.schoolClass'])
            ->get();

        return view('app.canteen.payments.index', compact('payments', 'schoolYears', 'schoolYearId', 'subscriptions'));
    }

    public function paymentsStore(Request $request)
    {
        $schoolId = session('current_school_id');
        $userId = auth()->id();

        $validated = $request->validate([
            'canteen_subscription_id' => 'required|exists:canteen_subscriptions,id',
            'canteen_installment_id' => 'nullable|exists:canteen_installments,id',
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,mobile_money,transfer',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $subscription = CanteenSubscription::findOrFail($validated['canteen_subscription_id']);

            // Créer le paiement
            CanteenPayment::create([
                'school_id' => $schoolId,
                'canteen_subscription_id' => $subscription->id,
                'canteen_installment_id' => $validated['canteen_installment_id'] ?? null,
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'payment_type' => $validated['canteen_installment_id'] ? 'installment' : 'advance',
                'reference' => $validated['reference'] ?? null,
                'received_by' => $userId,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Mettre à jour les montants de l'abonnement
            $subscription->paid_amount += $validated['amount'];
            $subscription->remaining_amount = $subscription->total_amount - $subscription->paid_amount;
            $subscription->save();

            // Si un échéance est spécifiée, mettre à jour son montant payé
            if ($validated['canteen_installment_id']) {
                $installment = CanteenInstallment::find($validated['canteen_installment_id']);
                $installment->paid_amount += $validated['amount'];
                $installment->status = $installment->paid_amount >= $installment->amount ? 'paid' : 'partial';
                $installment->save();
            }

            DB::commit();

            return redirect()->route('app.canteen.payments.index', ['school_year_id' => $subscription->school_year_id])
                ->with('success', '✅ Paiement enregistré avec succès !');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur : ' . $e->getMessage()]);
        }
    }

    // ==========================================
    // RAPPORTS CANTINE
    // ==========================================

    public function unpaidByClass(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));

        $classes = DB::table('school_classes')
            ->select(
                'school_classes.id as class_id',
                'school_classes.name as class_name',
                DB::raw('COUNT(DISTINCT canteen_subscriptions.student_id) as total_students'),
                DB::raw('COALESCE(SUM(canteen_subscriptions.total_amount), 0) as total_expected'),
                DB::raw('COALESCE(SUM(canteen_subscriptions.paid_amount), 0) as total_paid'),
                DB::raw('COALESCE(SUM(canteen_subscriptions.remaining_amount), 0) as total_unpaid')
            )
            ->join('canteen_rates', 'school_classes.id', '=', 'canteen_rates.school_class_id')
            ->join('canteen_subscriptions', 'canteen_rates.id', '=', 'canteen_subscriptions.canteen_rate_id')
            ->where('canteen_rates.school_id', $schoolId)
            ->where('canteen_rates.school_year_id', $schoolYearId)
            ->groupBy('school_classes.id', 'school_classes.name')
            ->orderBy('school_classes.name')
            ->get()
            ->map(function ($class) {
                $class->recovery_rate = $class->total_expected > 0 
                    ? round(($class->total_paid / $class->total_expected) * 100, 1) 
                    : 0;
                return $class;
            });

        $globalStats = (object) [
            'total_expected' => $classes->sum('total_expected'),
            'total_paid' => $classes->sum('total_paid'),
            'total_unpaid' => $classes->sum('total_unpaid'),
            'recovery_rate' => $classes->sum('total_expected') > 0 
                ? round(($classes->sum('total_paid') / $classes->sum('total_expected')) * 100, 1) 
                : 0,
        ];

        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();

        return view('app.canteen.reports.unpaid_by_class', compact('classes', 'globalStats', 'schoolYears', 'schoolYearId'));
    }

    public function classDetail(Request $request, $classId)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));

        $class = SchoolClass::findOrFail($classId);

        $students = DB::table('students')
            ->select(
                'students.id as student_id',
                'students.matricule',
                'students.first_name',
                'students.last_name',
                DB::raw('COALESCE(SUM(canteen_subscriptions.total_amount), 0) as total_du'),
                DB::raw('COALESCE(SUM(canteen_subscriptions.paid_amount), 0) as total_paye'),
                DB::raw('COALESCE(SUM(canteen_subscriptions.remaining_amount), 0) as total_reste')
            )
            ->join('canteen_subscriptions', 'students.id', '=', 'canteen_subscriptions.student_id')
            ->join('canteen_rates', 'canteen_subscriptions.canteen_rate_id', '=', 'canteen_rates.id')
            ->where('canteen_rates.school_class_id', $classId)
            ->where('canteen_rates.school_year_id', $schoolYearId)
            ->groupBy('students.id', 'students.matricule', 'students.first_name', 'students.last_name')
            ->orderBy('students.last_name')
            ->orderBy('students.first_name')
            ->get()
            ->map(function ($student) {
                $total = (float) $student->total_du;
                $paye = (float) $student->total_paye;
                $student->payment_rate = $total > 0 ? round(($paye / $total) * 100, 1) : 0;
                return $student;
            });

        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();

        return view('app.canteen.reports.class_detail', compact('class', 'students', 'schoolYears', 'schoolYearId'));
    }

    public function studentDetail(Request $request, $studentId)
    {
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));

        $student = Student::findOrFail($studentId);

        $subscription = CanteenSubscription::where('student_id', $studentId)
            ->where('school_year_id', $schoolYearId)
            ->with(['canteenRate.schoolClass', 'installments', 'payments'])
            ->first();

        if (!$subscription) {
            return back()->with('error', 'Cet élève n\'est pas inscrit à la cantine pour cette année.');
        }

        $installments = $subscription->installments->sortBy('due_date');
        $payments = $subscription->payments->sortByDesc('payment_date');

        $totalDue = $subscription->total_amount;
        $totalPaid = $subscription->paid_amount;
        $totalRemaining = $subscription->remaining_amount;
        $paymentRate = $subscription->payment_rate;

        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();

        return view('app.canteen.reports.student_detail', compact(
            'student', 'subscription', 'installments', 'payments',
            'totalDue', 'totalPaid', 'totalRemaining', 'paymentRate',
            'schoolYears', 'schoolYearId'
        ));
    }

    // ==========================================
    // MÉTHODE HELPER : Génération des échéances
    // ==========================================

    private function generateInstallments(CanteenSubscription $subscription, CanteenRate $rate)
    {
        $startDate = Carbon::parse($rate->start_month . '-01');
        $endDate = Carbon::parse($rate->end_month . '-01');

        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            CanteenInstallment::create([
                'canteen_subscription_id' => $subscription->id,
                'month' => $currentDate->format('Y-m'),
                'amount' => $rate->monthly_rate,
                'paid_amount' => 0,
                'due_date' => $currentDate->copy()->day(5), // Échéance le 5 de chaque mois
                'status' => 'pending',
            ]);

            $currentDate->addMonth();
        }
    }
}