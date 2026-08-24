<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\GouterInstallment;
use App\Models\GouterPayment;
use App\Models\GouterRate;
use App\Models\GouterSubscription;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GouterController extends Controller
{
    // ==========================================
    // MÉTHODES AJAX POUR LE FORMULAIRE DYNAMIQUE
    // ==========================================

    public function getMaternelleClasses(Request $request)
    {
        $schoolId = session('current_school_id');

        $classes = SchoolClass::where('school_id', $schoolId)
            ->whereRaw('LOWER(cycle) = ?', ['maternelle'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($classes);
    }

    public function getStudentsByClass(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->school_year_id ?? SchoolYear::where('school_id', $schoolId)->where('is_active', true)->value('id');

        // On se base sur l'affectation de classe (student_school_class), pas sur
        // les inscriptions (enrollments) : certains élèves affectés à une classe
        // n'ont pas de ligne d'inscription pour l'année, ce qui les faisait
        // disparaître de cette liste alors qu'ils sont bien dans la classe.
        $students = Student::where('school_id', $schoolId)
            ->whereHas('classes', function ($q) use ($request, $schoolYearId) {
                $q->where('school_classes.id', $request->class_id)
                    ->where('student_school_class.school_year_id', $schoolYearId);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'matricule', 'first_name', 'last_name']);

        return response()->json($students);
    }

    public function getSubscriptionsByClass(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->query('school_year_id');
        $classId = $request->query('class_id');

        if (!$schoolYearId || !$classId) {
            return response()->json([]);
        }

        $subscriptions = GouterSubscription::where('school_id', $schoolId)
            ->where('school_year_id', $schoolYearId)
            ->whereHas('gouterRate', fn ($q) => $q->where('school_class_id', $classId))
            ->whereHas('installments', fn ($q) => $q->where('status', '!=', 'paid'))
            ->with([
                'student',
                'gouterRate.schoolClass',
                'installments' => fn ($q) => $q->where('status', '!=', 'paid')->orderBy('due_date', 'asc'),
            ])
            ->get()
            ->map(function ($sub) {
                // remaining_amount est maintenu à jour automatiquement par le hook
                // GouterPayment::booted() (voir GouterSubscription::recalculateAmounts()).
                return [
                    'id' => $sub->id,
                    'student_name' => $sub->student ? ($sub->student->last_name . ' ' . $sub->student->first_name) : 'Élève inconnu',
                    'matricule' => $sub->student->matricule ?? 'N/A',
                    'remaining' => $sub->remaining_amount,
                    'class_name' => $sub->gouterRate->schoolClass->name ?? 'N/A',
                    'unpaid_installments' => $sub->installments->pluck('label'),
                ];
            });

        return response()->json($subscriptions);
    }

    // ==========================================
    // GESTION DES TARIFS
    // ==========================================

    public function ratesIndex(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));

        $rates = GouterRate::where('gouter_rates.school_id', $schoolId)
            ->where('gouter_rates.school_year_id', $schoolYearId)
            ->join('school_classes', 'gouter_rates.school_class_id', '=', 'school_classes.id')
            ->select('gouter_rates.*')
            ->orderBy('school_classes.name', 'asc')
            ->with('schoolClass')
            ->get();

        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();

        return view('app.gouter.rates.index', compact('rates', 'schoolYears', 'schoolYearId'));
    }

    public function ratesCreate(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));

        $classes = SchoolClass::where('school_id', $schoolId)
            ->whereRaw('LOWER(cycle) = ?', ['maternelle'])
            ->orderBy('name')
            ->get();
        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();

        return view('app.gouter.rates.create', compact('classes', 'schoolYears', 'schoolYearId'));
    }

    public function ratesStore(Request $request)
    {
        $schoolId = session('current_school_id');

        $validated = $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'school_class_id' => 'required|exists:school_classes,id',
            'total_amount' => 'required|numeric|min:0',
            'payment_modality' => 'required|in:unique,mensuel,trimestriel,semestriel',
            'description' => 'nullable|string|max:500',
        ]);

        $class = SchoolClass::where('school_id', $schoolId)->findOrFail($validated['school_class_id']);
        if (strtolower($class->cycle) !== 'maternelle') {
            return back()->withErrors([
                'school_class_id' => 'Le goûter est réservé aux classes de maternelle.',
            ])->withInput();
        }

        $existingRate = GouterRate::where('school_id', $schoolId)
            ->where('school_year_id', $validated['school_year_id'])
            ->where('school_class_id', $validated['school_class_id'])
            ->first();

        if ($existingRate) {
            return back()->withErrors([
                'school_class_id' => 'Un tarif de goûter existe déjà pour cette classe durant cette année scolaire.',
            ])->withInput();
        }

        $numberOfInstallments = self::installmentsForModality($validated['payment_modality']);

        GouterRate::create([
            'school_id' => $schoolId,
            'school_year_id' => $validated['school_year_id'],
            'school_class_id' => $validated['school_class_id'],
            'total_amount' => $validated['total_amount'],
            'payment_modality' => $validated['payment_modality'],
            'number_of_installments' => $numberOfInstallments,
            'installment_amount' => round($validated['total_amount'] / $numberOfInstallments, 2),
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('gouter.rates.index', ['school_year_id' => $validated['school_year_id']])
            ->with('success', '✅ Tarif de goûter créé avec succès !');
    }

    public function ratesEdit($id)
    {
        $rate = GouterRate::where('school_id', session('current_school_id'))->findOrFail($id);
        $classes = SchoolClass::where('school_id', $rate->school_id)
            ->whereRaw('LOWER(cycle) = ?', ['maternelle'])
            ->orderBy('name')
            ->get();
        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();

        return view('app.gouter.rates.edit', compact('rate', 'classes', 'schoolYears'));
    }

    public function ratesUpdate(Request $request, $id)
    {
        $rate = GouterRate::where('school_id', session('current_school_id'))->findOrFail($id);

        $validated = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'total_amount' => 'required|numeric|min:0',
            'payment_modality' => 'required|in:unique,mensuel,trimestriel,semestriel',
            'description' => 'nullable|string|max:500',
        ]);

        $numberOfInstallments = self::installmentsForModality($validated['payment_modality']);

        $rate->update([
            'school_class_id' => $validated['school_class_id'],
            'total_amount' => $validated['total_amount'],
            'payment_modality' => $validated['payment_modality'],
            'number_of_installments' => $numberOfInstallments,
            'installment_amount' => round($validated['total_amount'] / $numberOfInstallments, 2),
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('gouter.rates.index', ['school_year_id' => $rate->school_year_id])
            ->with('success', '✅ Tarif mis à jour avec succès !');
    }

    public function ratesDestroy($id)
    {
        $rate = GouterRate::where('school_id', session('current_school_id'))->findOrFail($id);
        $schoolYearId = $rate->school_year_id;
        $rate->delete();

        return redirect()->route('gouter.rates.index', ['school_year_id' => $schoolYearId])
            ->with('success', '✅ Tarif supprimé avec succès !');
    }

    /**
     * Nombre d'échéances associé à chaque modalité (même principe que ClassFeeController pour la
     * scolarité : unique = 1 versement, mensuel = 10 mois d'année scolaire, etc.).
     */
    private static function installmentsForModality(string $modality): int
    {
        return match ($modality) {
            'mensuel' => 10,
            'trimestriel' => 3,
            'semestriel' => 2,
            default => 1,
        };
    }

    // ==========================================
    // INSCRIPTIONS DES ÉLÈVES
    // ==========================================

    public function subscriptionsIndex(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));
        $classId = $request->get('class_id');

        $query = GouterSubscription::where('school_id', $schoolId)
            ->where('school_year_id', $schoolYearId)
            ->with(['student', 'gouterRate.schoolClass']);

        if ($classId) {
            $query->whereHas('gouterRate', fn ($q) => $q->where('school_class_id', $classId));
        }

        $subscriptions = $query->get();
        $classes = SchoolClass::where('school_id', $schoolId)
            ->whereRaw('LOWER(cycle) = ?', ['maternelle'])
            ->orderBy('name')
            ->get();
        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();

        $rates = GouterRate::where('school_id', $schoolId)
            ->where('school_year_id', $schoolYearId)
            ->with('schoolClass')
            ->get();

        return view('app.gouter.subscriptions.index', compact(
            'subscriptions', 'classes', 'schoolYears', 'schoolYearId', 'classId', 'rates'
        ));
    }

    public function subscriptionsStore(Request $request)
    {
        $schoolId = session('current_school_id');

        $validated = $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'students' => 'required|array|min:1',
            'students.*.selected' => 'required|boolean',
            'students.*.amount' => 'required|numeric|min:0',
            'students.*.payment_method' => 'required|in:cash,mobile_money,transfer,check',
        ]);

        $schoolYear = SchoolYear::findOrFail($validated['school_year_id']);

        DB::beginTransaction();
        try {
            $successCount = 0;
            $skipCount = 0;
            $errors = [];

            foreach ($validated['students'] as $studentId => $data) {
                if (!isset($data['selected']) || !$data['selected']) {
                    continue;
                }

                $enrollment = \App\Models\Enrollment::where('student_id', $studentId)
                    ->where('school_year_id', $validated['school_year_id'])
                    ->first();

                if (!$enrollment) {
                    $errors[] = "L'élève n'est pas inscrit dans cette année scolaire.";
                    continue;
                }

                $rate = GouterRate::where('school_id', $schoolId)
                    ->where('school_year_id', $validated['school_year_id'])
                    ->where('school_class_id', $enrollment->school_class_id)
                    ->first();

                if (!$rate) {
                    $errors[] = "Aucun tarif de goûter défini pour la classe de cet élève.";
                    continue;
                }

                $existing = GouterSubscription::where('student_id', $studentId)
                    ->where('school_year_id', $validated['school_year_id'])
                    ->first();

                if ($existing) {
                    $skipCount++;
                    continue;
                }

                $paidNow = $data['amount'];

                $subscription = GouterSubscription::create([
                    'school_id' => $schoolId,
                    'student_id' => $studentId,
                    'school_year_id' => $validated['school_year_id'],
                    'gouter_rate_id' => $rate->id,
                    'total_amount' => $rate->total_amount,
                    'paid_amount' => 0,
                    'remaining_amount' => $rate->total_amount,
                    'status' => 'active',
                ]);

                // Génération des échéances selon la modalité du tarif, ancrées sur le début de
                // l'année scolaire (comme les frais de scolarité).
                $currentDate = Carbon::parse($schoolYear->start_date);
                for ($i = 1; $i <= $rate->number_of_installments; $i++) {
                    match ($rate->payment_modality) {
                        'mensuel' => $currentDate->addMonth(),
                        'trimestriel' => $currentDate->addMonths(3),
                        'semestriel' => $currentDate->addMonths(6),
                        default => $currentDate->addMonth(),
                    };

                    GouterInstallment::create([
                        'gouter_subscription_id' => $subscription->id,
                        'label' => "Échéance {$i}/{$rate->number_of_installments}",
                        'amount' => $rate->installment_amount,
                        'paid_amount' => 0,
                        'due_date' => $currentDate->copy(),
                        'status' => 'pending',
                    ]);
                }

                if ($paidNow > 0 && !empty($data['payment_method'])) {
                    self::recordPayment($schoolId, $subscription, $paidNow, now(), $data['payment_method'], 'initial');
                }

                $successCount++;
            }

            DB::commit();

            $message = "✅ {$successCount} élève(s) inscrit(s) au goûter avec succès !";
            if ($skipCount > 0) {
                $message .= " ({$skipCount} déjà inscrit(s) ignoré(s)).";
            }
            if (!empty($errors)) {
                $message .= ' | ' . implode(', ', array_unique($errors));
            }

            return redirect()->route('gouter.subscriptions.index', ['school_year_id' => $validated['school_year_id']])
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur : ' . $e->getMessage()])->withInput();
        }
    }

    public function subscriptionsDestroy($id)
    {
        $subscription = GouterSubscription::where('school_id', session('current_school_id'))->findOrFail($id);
        $schoolYearId = $subscription->school_year_id;
        $subscription->delete();

        return redirect()->route('gouter.subscriptions.index', ['school_year_id' => $schoolYearId])
            ->with('success', '✅ Inscription annulée avec succès !');
    }

    // ==========================================
    // PAIEMENTS
    // ==========================================

    public function paymentsIndex(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));
        $classId = $request->get('class_id', '');

        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();
        $classes = SchoolClass::where('school_id', $schoolId)
            ->whereRaw('LOWER(cycle) = ?', ['maternelle'])
            ->orderBy('name')
            ->get();

        if (!$classId) {
            $payments = GouterPayment::where('id', 0)->paginate(20);

            return view('app.gouter.payments.index', compact(
                'payments', 'schoolYears', 'schoolYearId', 'classId', 'classes'
            ));
        }

        $payments = GouterPayment::where('school_id', $schoolId)
            ->whereHas('subscription', fn ($q) => $q->where('school_year_id', $schoolYearId))
            ->whereHas('subscription.gouterRate', fn ($q) => $q->where('school_class_id', $classId))
            ->with(['subscription.student', 'subscription.gouterRate.schoolClass', 'receivedByUser'])
            ->orderBy('payment_date', 'desc')
            ->paginate(20);

        return view('app.gouter.payments.index', compact(
            'payments', 'schoolYears', 'schoolYearId', 'classId', 'classes'
        ));
    }

    public function paymentsStore(Request $request)
    {
        $schoolId = session('current_school_id');

        $validated = $request->validate([
            'gouter_subscription_id' => 'required|exists:gouter_subscriptions,id',
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,mobile_money,transfer,check',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $subscription = GouterSubscription::where('school_id', $schoolId)->findOrFail($validated['gouter_subscription_id']);

            self::recordPayment(
                $schoolId,
                $subscription,
                $validated['amount'],
                $validated['payment_date'],
                $validated['payment_method'],
                'installment',
                $validated['reference'] ?? null,
                $validated['notes'] ?? null
            );

            DB::commit();

            return redirect()->route('gouter.payments.index', ['school_year_id' => $subscription->school_year_id, 'class_id' => $subscription->gouterRate->school_class_id])
                ->with('success', '✅ Paiement enregistré et réparti sur les échéances avec succès !');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur : ' . $e->getMessage()]);
        }
    }

    /**
     * Enregistre un paiement et le répartit en FIFO sur les échéances impayées les plus anciennes.
     */
    private static function recordPayment(
        int $schoolId,
        GouterSubscription $subscription,
        float $amount,
        $paymentDate,
        string $paymentMethod,
        string $paymentType,
        ?string $reference = null,
        ?string $notes = null
    ): GouterPayment {
        $payment = GouterPayment::create([
            'school_id' => $schoolId,
            'gouter_subscription_id' => $subscription->id,
            'amount' => $amount,
            'payment_date' => $paymentDate,
            'payment_method' => $paymentMethod,
            'payment_type' => $paymentType,
            'reference' => $reference,
            'received_by' => auth()->id(),
            'notes' => $notes,
        ]);
        // Le hook GouterPayment::booted() recalcule automatiquement
        // paid_amount/remaining_amount/status sur l'abonnement.

        $installments = $subscription->installments()->orderBy('due_date', 'asc')->get();
        $remaining = $amount;

        foreach ($installments as $inst) {
            if ($remaining <= 0) {
                break;
            }

            $due = $inst->amount - $inst->paid_amount;
            if ($due <= 0) {
                continue;
            }

            if ($remaining >= $due) {
                $inst->paid_amount = $inst->amount;
                $inst->status = 'paid';
                $remaining -= $due;
            } else {
                $inst->paid_amount += $remaining;
                $inst->status = 'partial';
                $remaining = 0;
            }
            $inst->save();
        }

        return $payment;
    }

    public function receipt(GouterPayment $payment)
    {
        if ($payment->school_id !== session('current_school_id')) {
            abort(403);
        }

        $payment->load(['subscription.student', 'subscription.gouterRate.schoolClass', 'subscription.schoolYear', 'school', 'receivedByUser']);

        $subscription = $payment->subscription;
        $student = $subscription->student;
        $school = $payment->school;
        $schoolClass = $subscription->gouterRate->schoolClass ?? null;
        $schoolYear = $subscription->schoolYear;

        $pendingInstallments = $subscription->installments()
            ->where('status', '!=', 'paid')
            ->orderBy('due_date', 'asc')
            ->get();

        $pdf = Pdf::loadView('pdf.gouter-receipt', compact(
            'payment', 'student', 'school', 'schoolClass', 'schoolYear', 'subscription', 'pendingInstallments'
        ));

        $filename = 'Recu_Gouter_' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) . '.pdf';

        return $pdf->download($filename);
    }

    // ==========================================
    // RAPPORTS
    // ==========================================

    public function unpaidByClass(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));
        $filterClassId = $request->get('class_id', '');

        $query = DB::table('school_classes')
            ->select(
                'school_classes.id as class_id',
                'school_classes.name as class_name',
                DB::raw('COUNT(DISTINCT gouter_subscriptions.student_id) as total_students'),
                DB::raw('COALESCE(SUM(gouter_subscriptions.total_amount), 0) as total_expected'),
                DB::raw('COALESCE(SUM(gouter_subscriptions.paid_amount), 0) as total_paid'),
                DB::raw('COALESCE(SUM(gouter_subscriptions.remaining_amount), 0) as total_unpaid')
            )
            ->join('gouter_rates', 'school_classes.id', '=', 'gouter_rates.school_class_id')
            ->join('gouter_subscriptions', 'gouter_rates.id', '=', 'gouter_subscriptions.gouter_rate_id')
            ->where('gouter_rates.school_id', $schoolId)
            ->where('gouter_rates.school_year_id', $schoolYearId);

        if ($filterClassId) {
            $query->where('school_classes.id', $filterClassId);
        }

        $classes = $query->groupBy('school_classes.id', 'school_classes.name')
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
        $allClasses = SchoolClass::where('school_id', $schoolId)
            ->whereRaw('LOWER(cycle) = ?', ['maternelle'])
            ->orderBy('name')
            ->get();

        return view('app.gouter.reports.unpaid_by_class', compact(
            'classes', 'globalStats', 'schoolYears', 'schoolYearId', 'allClasses', 'filterClassId'
        ));
    }
}
