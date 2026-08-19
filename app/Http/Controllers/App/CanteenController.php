<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\CanteenInstallment;
use App\Models\CanteenPayment;
use App\Models\CanteenRate;
use App\Models\CanteenSubscription;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CanteenController extends Controller
{
    // ==========================================
    // MÉTHODES AJAX POUR LE FORMULAIRE DYNAMIQUE
    // ==========================================

    public function getClassesByCycle(Request $request)
    {
        $schoolId = session('current_school_id');
        $cycle = $request->query('cycle');

        $classes = SchoolClass::where('school_id', $schoolId)
            ->whereRaw('LOWER(cycle) = ?', [strtolower($cycle)])
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($classes);
    }

    public function getStudentsByClass(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->school_year_id ?? SchoolYear::where('is_active', true)->value('id');

        $students = Student::where('school_id', $schoolId)
            ->whereHas('enrollments', function ($q) use ($request, $schoolYearId) {
                $q->where('school_class_id', $request->class_id)
                    ->where('school_year_id', $schoolYearId);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'matricule', 'first_name', 'last_name']);

        return response()->json($students);
    }

    // public function getSubscriptionsByClass(Request $request)
    // {
    //     $schoolId = session('current_school_id');
    //     $schoolYearId = $request->query('school_year_id');
    //     $classId = $request->query('class_id');

    //     if (!$schoolYearId || !$classId) {
    //         return response()->json([]);
    //     }

    //     $subscriptions = CanteenSubscription::where('school_id', $schoolId)
    //         ->where('school_year_id', $schoolYearId)
    //         ->where('remaining_amount', '>', 0) // ✅ RÉACTIVÉ : On ne montre que ceux qui ont un reste à payer
    //         ->whereHas('canteenRate', function($q) use ($classId) {
    //             $q->where('school_class_id', $classId);
    //         })
    //         ->with([
    //             'student', 
    //             'canteenRate.schoolClass', 
    //             'installments' => function($q) {
    //                 $q->where('status', '!=', 'paid')->orderBy('due_date', 'asc');
    //             }
    //         ])
    //         ->get()
    //         ->map(function($sub) {
    //             $unpaidMonths = $sub->installments->map(function($inst) {
    //                 return \Carbon\Carbon::parse($inst->month . '-01')->translatedFormat('F Y');
    //             })->toArray();

    //             return [
    //                 'id' => $sub->id,
    //                 'student_name' => $sub->student ? ($sub->student->last_name . ' ' . $sub->student->first_name) : 'Élève inconnu',
    //                 'matricule' => $sub->student ? $sub->student->matricule : 'N/A',
    //                 'remaining' => $sub->remaining_amount,
    //                 'class_name' => $sub->canteenRate && $sub->canteenRate->schoolClass ? $sub->canteenRate->schoolClass->name : 'N/A',
    //                 'unpaid_months' => $unpaidMonths
    //             ];
    //         });

    //     return response()->json($subscriptions);
    // }

    // ==========================================
    // GESTION DES TARIFS
    // ==========================================

    public function ratesIndex(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));

        $rates = CanteenRate::where('canteen_rates.school_id', $schoolId)
            ->where('canteen_rates.school_year_id', $schoolYearId)
            ->join('school_classes', 'canteen_rates.school_class_id', '=', 'school_classes.id')
            ->select('canteen_rates.*')
            ->orderBy('school_classes.name', 'asc')
            ->with('schoolClass')
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

        $existingRate = CanteenRate::where('school_id', $schoolId)
            ->where('school_year_id', $validated['school_year_id'])
            ->where('school_class_id', $validated['school_class_id'])
            ->first();

        if ($existingRate) {
            return back()->withErrors([
                'school_class_id' => 'Un tarif existe déjà pour cette classe durant cette année scolaire.'
            ])->withInput();
        }

        $validated['school_id'] = $schoolId;
        CanteenRate::create($validated);

        return redirect()->route('canteen.rates.index', ['school_year_id' => $validated['school_year_id']])
            ->with('success', '✅ Tarif de cantine créé avec succès !');
    }

    public function ratesEdit($id)
    {
        $rate = CanteenRate::where('school_id', session('current_school_id'))->findOrFail($id);
        $classes = SchoolClass::where('school_id', $rate->school_id)->orderBy('name')->get();
        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();

        return view('app.canteen.rates.edit', compact('rate', 'classes', 'schoolYears'));
    }

    public function ratesUpdate(Request $request, $id)
    {
        $rate = CanteenRate::where('school_id', session('current_school_id'))->findOrFail($id);

        $validated = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'monthly_rate' => 'required|numeric|min:0',
            'months_count' => 'required|integer|min:1|max:12',
            'start_month' => 'required|date_format:Y-m',
            'end_month' => 'required|date_format:Y-m|after_or_equal:start_month',
            'description' => 'nullable|string|max:500',
        ]);

        $rate->update($validated);

        return redirect()->route('canteen.rates.index', ['school_year_id' => $rate->school_year_id])
            ->with('success', '✅ Tarif mis à jour avec succès !');
    }

    public function ratesDestroy($id)
    {
        $rate = CanteenRate::where('school_id', session('current_school_id'))->findOrFail($id);
        $schoolYearId = $rate->school_year_id;
        $rate->delete();

        return redirect()->route('canteen.rates.index', ['school_year_id' => $schoolYearId])
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

        $rates = CanteenRate::where('school_id', $schoolId)
            ->where('school_year_id', $schoolYearId)
            ->with('schoolClass')
            ->get();

        return view('app.canteen.subscriptions.index', compact(
            'subscriptions', 'classes', 'schoolYears', 'schoolYearId', 'classId', 'rates'
        ));
    }

    // public function subscriptionsStore(Request $request)
    // {
    //     $schoolId = session('current_school_id');

    //     $validated = $request->validate([
    //         'school_year_id' => 'required|exists:school_years,id',
    //         'enrollments' => 'required|array|min:1',
    //         'enrollments.*.selected' => 'required|boolean',
    //         'enrollments.*.months' => 'required|array|min:1',
    //         'enrollments.*.months.*' => 'required|date_format:Y-m',
    //         'enrollments.*.amount' => 'required|numeric|min:0',
    //         'enrollments.*.payment_method' => 'required|in:cash,mobile_money,transfer,check',
    //     ]);

    //     DB::beginTransaction();
    //     try {
    //         $successCount = 0;
    //         $skipCount = 0;

    //         foreach ($validated['enrollments'] as $studentId => $data) {
    //             if (!isset($data['selected']) || !$data['selected']) {
    //                 continue;
    //             }

    //             $enrollment = \App\Models\Enrollment::where('student_id', $studentId)
    //                 ->where('school_year_id', $validated['school_year_id'])
    //                 ->first();

    //             if (!$enrollment) continue;

    //             $rate = CanteenRate::where('school_id', $schoolId)
    //                 ->where('school_year_id', $validated['school_year_id'])
    //                 ->where('school_class_id', $enrollment->school_class_id)
    //                 ->first();

    //             if (!$rate) continue;

    //             $existing = CanteenSubscription::where('student_id', $studentId)
    //                 ->where('school_year_id', $validated['school_year_id'])
    //                 ->first();

    //             if ($existing) {
    //                 $skipCount++;
    //                 continue;
    //             }

    //             // ✅ CORRECTION MAJEURE : Calcul correct des montants
    //             $numberOfMonths = count($data['months']);
    //             $totalAmount = $numberOfMonths * $rate->monthly_rate; // Montant total de l'abonnement
    //             $paidNow = $data['amount']; // Montant payé aujourd'hui
    //             $remainingAmount = $totalAmount - $paidNow; // Ce qui reste à payer

    //             $subscription = CanteenSubscription::create([
    //                 'school_id' => $schoolId,
    //                 'student_id' => $studentId,
    //                 'school_year_id' => $validated['school_year_id'],
    //                 'canteen_rate_id' => $rate->id,
    //                 'total_amount' => $totalAmount,
    //                 'paid_amount' => $paidNow,
    //                 'remaining_amount' => $remainingAmount,
    //                 'status' => $remainingAmount <= 0 ? 'paid' : 'active',
    //             ]);

    //             foreach ($data['months'] as $month) {
    //                 CanteenInstallment::create([
    //                     'canteen_subscription_id' => $subscription->id,
    //                     'month' => $month,
    //                     'amount' => $rate->monthly_rate,
    //                     'paid_amount' => 0,
    //                     'due_date' => Carbon::parse($month . '-01')->day(5),
    //                     'status' => 'pending',
    //                 ]);
    //             }

    //             // Répartir le paiement initial sur les échéances (FIFO)
    //             if ($paidNow > 0 && !empty($data['payment_method'])) {
    //                 CanteenPayment::create([
    //                     'school_id' => $schoolId,
    //                     'canteen_subscription_id' => $subscription->id,
    //                     'amount' => $paidNow,
    //                     'payment_date' => now(),
    //                     'payment_method' => $data['payment_method'],
    //                     'payment_type' => 'initial',
    //                     'received_by' => auth()->id(),
    //                 ]);

    //                 $installments = $subscription->installments()->orderBy('due_date', 'asc')->get();
    //                 $remainingPaymentToAllocate = $paidNow;

    //                 foreach ($installments as $inst) {
    //                     if ($remainingPaymentToAllocate <= 0) break;
    //                     $dueAmount = $inst->amount - $inst->paid_amount;

    //                     if ($remainingPaymentToAllocate >= $dueAmount) {
    //                         $inst->paid_amount = $inst->amount;
    //                         $inst->status = 'paid';
    //                         $remainingPaymentToAllocate -= $dueAmount;
    //                     } else {
    //                         $inst->paid_amount += $remainingPaymentToAllocate;
    //                         $inst->status = 'partial';
    //                         $remainingPaymentToAllocate = 0;
    //                     }
    //                     $inst->save();
    //                 }
    //             }

    //             $successCount++;
    //         }

    //         DB::commit();

    //         $message = "✅ $successCount élève(s) inscrit(s) à la cantine avec succès !";
    //         if ($skipCount > 0) $message .= " ($skipCount déjà inscrit(s) ignoré(s)).";

    //         return redirect()->route('canteen.subscriptions.index', ['school_year_id' => $validated['school_year_id']])
    //             ->with('success', $message);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return back()->withErrors(['error' => 'Erreur : ' . $e->getMessage()])->withInput();
    //     }
    // }

    public function subscriptionsDestroy($id)
    {
        $subscription = CanteenSubscription::where('school_id', session('current_school_id'))->findOrFail($id);
        $schoolYearId = $subscription->school_year_id;
        $subscription->delete();

        return redirect()->route('canteen.subscriptions.index', ['school_year_id' => $schoolYearId])
            ->with('success', '✅ Inscription annulée avec succès !');
    }

    // ==========================================
    // PAIEMENTS
    // ==========================================

    // public function paymentsIndex(Request $request)
    // {
    //     $schoolId = session('current_school_id');
    //     $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));
    //     $classId = $request->get('class_id', ''); // ✅ AJOUTÉ pour le filtre

    //     $payments = CanteenPayment::where('school_id', $schoolId)
    //         ->whereHas('subscription', fn($q) => $q->where('school_year_id', $schoolYearId))
    //         ->with(['subscription.student', 'subscription.canteenRate.schoolClass', 'receivedByUser'])
    //         ->orderBy('payment_date', 'desc')
    //         ->paginate(20);

    //     $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();
    //     $classes = \App\Models\SchoolClass::where('school_id', $schoolId)->orderBy('name')->get();

    //     return view('app.canteen.payments.index', compact('payments', 'schoolYears', 'schoolYearId', 'classId', 'classes'));
    // }

    //     public function paymentsIndex(Request $request)
    // {
    //     $schoolId = session('current_school_id');
    //     $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));
    //     $classId = $request->get('class_id', '');
    //     $studentId = $request->get('student_id', '');

    //     // Construction de la requête avec les filtres
    //     $query = CanteenPayment::where('school_id', $schoolId)
    //         ->whereHas('subscription', fn($q) => $q->where('school_year_id', $schoolYearId))
    //         ->with(['subscription.student', 'subscription.canteenRate.schoolClass', 'receivedByUser'])
    //         ->orderBy('payment_date', 'desc');

    //     if ($classId) {
    //         $query->whereHas('subscription.canteenRate', fn($q) => $q->where('school_class_id', $classId));
    //     }

    //     if ($studentId) {
    //         $query->whereHas('subscription', fn($q) => $q->where('student_id', $studentId));
    //     }

    //     $payments = $query->paginate(20);

    //     $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();
    //     $classes = \App\Models\SchoolClass::where('school_id', $schoolId)->orderBy('name')->get();
        
    //     // Récupérer la liste des élèves inscrits à la cantine cette année pour le filtre
    //     $students = \App\Models\CanteenSubscription::where('school_id', $schoolId)
    //         ->where('school_year_id', $schoolYearId)
    //         ->with('student')
    //         ->get()
    //         ->pluck('student')
    //         ->unique('id')
    //         ->sortBy(function($student) {
    //             return $student->last_name . ' ' . $student->first_name;
    //         });

    //     return view('app.canteen.payments.index', compact(
    //         'payments', 'schoolYears', 'schoolYearId', 'classId', 'studentId', 'classes', 'students'
    //     ));
    // }


    //     public function paymentsIndex(Request $request)
    // {
    //     $schoolId = session('current_school_id');
    //     $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));
    //     $classId = $request->get('class_id', '');
    //     $month = $request->get('month', '');

    //     $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();
    //     $classes = \App\Models\SchoolClass::where('school_id', $schoolId)->orderBy('name')->get();

    //     $availableMonths = [
    //         '2026-09' => 'Septembre 2026', '2026-10' => 'Octobre 2026', '2026-11' => 'Novembre 2026',
    //         '2026-12' => 'Décembre 2026', '2027-01' => 'Janvier 2027', '2027-02' => 'Février 2027',
    //         '2027-03' => 'Mars 2027', '2027-04' => 'Avril 2027', '2027-05' => 'Mai 2027', '2027-06' => 'Juin 2027',
    //     ];

    //     // ✅ Si aucun filtre n'est appliqué, on retourne une liste vide
    //     if (!$classId && !$month) {
    //         $payments = collect(); // Collection vide
    //         return view('app.canteen.payments.index', compact(
    //             'payments', 'schoolYears', 'schoolYearId', 'classId', 'month', 'classes', 'availableMonths'
    //         ));
    //     }

    //     // Construction de la requête avec filtres
    //     $query = CanteenPayment::where('school_id', $schoolId)
    //         ->whereHas('subscription', fn($q) => $q->where('school_year_id', $schoolYearId))
    //         ->with(['subscription.student', 'subscription.canteenRate.schoolClass', 'receivedByUser'])
    //         ->orderBy('payment_date', 'desc');

    //     // Filtre par classe
    //     if ($classId) {
    //         $query->whereHas('subscription.canteenRate', fn($q) => $q->where('school_class_id', $classId));
    //     }

    //     // Filtre par mois : on cherche les paiements liés à des subscriptions qui ont un installment pour ce mois
    //     if ($month) {
    //         $query->whereHas('subscription.installments', fn($q) => $q->where('month', $month));
    //     }

    //     $payments = $query->paginate(20);

    //     return view('app.canteen.payments.index', compact(
    //         'payments', 'schoolYears', 'schoolYearId', 'classId', 'month', 'classes', 'availableMonths'
    //     ));
    // }

        public function paymentsIndex(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));
        $classId = $request->get('class_id', '');
        $month = $request->get('month', '');

        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();
        $classes = \App\Models\SchoolClass::where('school_id', $schoolId)->orderBy('name')->get();

        $availableMonths = [
            '2026-09' => 'Septembre 2026', '2026-10' => 'Octobre 2026', '2026-11' => 'Novembre 2026',
            '2026-12' => 'Décembre 2026', '2027-01' => 'Janvier 2027', '2027-02' => 'Février 2027',
            '2027-03' => 'Mars 2027', '2027-04' => 'Avril 2027', '2027-05' => 'Mai 2027', '2027-06' => 'Juin 2027',
        ];

        // ✅ CORRECTION : Retourner un paginateur vide (et non une collection) pour que hasPages() fonctionne
        if (!$classId && !$month) {
            $payments = \App\Models\CanteenPayment::where('id', 0)->paginate(20); 
            
            return view('app.canteen.payments.index', compact(
                'payments', 'schoolYears', 'schoolYearId', 'classId', 'month', 'classes', 'availableMonths'
            ));
        }

        // Construction de la requête avec filtres
        $query = CanteenPayment::where('school_id', $schoolId)
            ->whereHas('subscription', fn($q) => $q->where('school_year_id', $schoolYearId))
            ->with(['subscription.student', 'subscription.canteenRate.schoolClass', 'receivedByUser'])
            ->orderBy('payment_date', 'desc');

        if ($classId) {
            $query->whereHas('subscription.canteenRate', fn($q) => $q->where('school_class_id', $classId));
        }

        if ($month) {
            $query->whereHas('subscription.installments', fn($q) => $q->where('month', $month));
        }

        $payments = $query->paginate(20);

        return view('app.canteen.payments.index', compact(
            'payments', 'schoolYears', 'schoolYearId', 'classId', 'month', 'classes', 'availableMonths'
        ));
    }

    public function paymentsStore(Request $request)
    {
        $schoolId = session('current_school_id');
        $userId = auth()->id();

        $validated = $request->validate([
            'canteen_subscription_id' => 'required|exists:canteen_subscriptions,id',
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,mobile_money,transfer,check',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $subscription = CanteenSubscription::where('school_id', $schoolId)->findOrFail($validated['canteen_subscription_id']);
            $paymentAmount = $validated['amount'];

            CanteenPayment::create([
                'school_id' => $schoolId,
                'canteen_subscription_id' => $subscription->id,
                'amount' => $paymentAmount,
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'payment_type' => 'installment',
                'reference' => $validated['reference'] ?? null,
                'received_by' => $userId,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Répartir automatiquement l'argent sur les échéances (FIFO)
            $installments = $subscription->installments()->orderBy('due_date', 'asc')->get();
            $remainingPayment = $paymentAmount;

            foreach ($installments as $inst) {
                if ($remainingPayment <= 0) break;

                $dueAmount = $inst->amount - $inst->paid_amount;

                if ($remainingPayment >= $dueAmount) {
                    $inst->paid_amount = $inst->amount;
                    $inst->status = 'paid';
                    $remainingPayment -= $dueAmount;
                } else {
                    $inst->paid_amount += $remainingPayment;
                    $inst->status = 'partial';
                    $remainingPayment = 0;
                }
                $inst->save();
            }

            $subscription->paid_amount += $paymentAmount;
            $subscription->remaining_amount = $subscription->total_amount - $subscription->paid_amount;

            if ($subscription->remaining_amount <= 0) {
                $subscription->status = 'paid';
            }
            $subscription->save();

            DB::commit();

            return redirect()->route('canteen.payments.index', ['school_year_id' => $subscription->school_year_id])
                ->with('success', '✅ Paiement enregistré et réparti sur les échéances avec succès !');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur : ' . $e->getMessage()]);
        }
    }

    // ==========================================
    // RAPPORTS CANTINE
    // ==========================================

    public function classDetail(Request $request, $classId)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));
        $selectedMonth = $request->get('month', '');

        $class = SchoolClass::where('school_id', $schoolId)->findOrFail($classId);

        $subscriptions = CanteenSubscription::where('school_id', $schoolId)
            ->where('school_year_id', $schoolYearId)
            ->whereHas('canteenRate', fn($q) => $q->where('school_class_id', $classId))
            ->with(['student', 'installments' => function ($q) use ($selectedMonth) {
                if ($selectedMonth) {
                    $q->where('month', $selectedMonth);
                }
            }])
            ->get();

        $students = $subscriptions->map(function ($sub) use ($selectedMonth) {
            $student = $sub->student;
            $student->total_du = $sub->total_amount;
            $student->total_paye = $sub->paid_amount;
            $student->total_reste = $sub->remaining_amount;

            if ($selectedMonth) {
                $installment = $sub->installments->first();
                $student->month_status = $installment ? $installment->status : 'non_programmé';
                $student->month_paid_amount = $installment ? $installment->paid_amount : 0;
                $student->month_due_amount = $installment ? $installment->amount : 0;
            } else {
                $student->month_status = 'global';
                $student->month_paid_amount = $sub->paid_amount;
                $student->month_due_amount = $sub->total_amount;
            }

            $total = (float) $sub->total_amount;
            $paye = (float) $sub->paid_amount;
            $student->payment_rate = $total > 0 ? round(($paye / $total) * 100, 1) : 0;

            return $student;
        })->sortBy('last_name')->sortBy('first_name');

        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();

        $availableMonths = [
            '' => 'Tous les mois (Vue Globale)',
            '2026-09' => 'Septembre 2026', '2026-10' => 'Octobre 2026', '2026-11' => 'Novembre 2026',
            '2026-12' => 'Décembre 2026', '2027-01' => 'Janvier 2027', '2027-02' => 'Février 2027',
            '2027-03' => 'Mars 2027', '2027-04' => 'Avril 2027', '2027-05' => 'Mai 2027', '2027-06' => 'Juin 2027',
        ];

        return view('app.canteen.reports.class_detail', compact('class', 'students', 'schoolYears', 'schoolYearId', 'selectedMonth', 'availableMonths'));
    }

    public function studentDetail(Request $request, $studentId)
    {
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));
        $student = Student::where('school_id', session('current_school_id'))->findOrFail($studentId);

        $subscription = CanteenSubscription::where('student_id', $studentId)
            ->where('school_year_id', $schoolYearId)
            ->with(['canteenRate.schoolClass', 'installments', 'payments'])
            ->first();

        if (!$subscription) {
            return back()->with('error', 'Cet élève n\'est pas inscrit à la cantine pour cette année.');
        }

        $installments = $subscription->installments->sortBy('due_date');
        $payments = $subscription->payments->sortByDesc('payment_date');

        // Auto-réparation des statuts
        $remainingToAllocate = $subscription->paid_amount;
        foreach ($installments as $inst) {
            $dueAmount = $inst->amount;
            if ($remainingToAllocate >= $dueAmount) {
                $inst->paid_amount = $dueAmount;
                $inst->status = 'paid';
                $remainingToAllocate -= $dueAmount;
            } elseif ($remainingToAllocate > 0) {
                $inst->paid_amount = $remainingToAllocate;
                $inst->status = 'partial';
                $remainingToAllocate = 0;
            } else {
                $inst->paid_amount = 0;
                $inst->status = 'pending';
            }
            $inst->save();
        }

        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();

        return view('app.canteen.reports.student_detail', compact('student', 'subscription', 'installments', 'payments', 'schoolYears', 'schoolYearId'));
    }

    public function unpaidByClass(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));
        $filterClassId = $request->get('class_id', '');
        $filterMonth = $request->get('month', '');

        $query = DB::table('school_classes')
            ->select(
                'school_classes.id as class_id',
                'school_classes.name as class_name',
                DB::raw('COUNT(DISTINCT canteen_subscriptions.student_id) as total_students')
            )
            ->join('canteen_rates', 'school_classes.id', '=', 'canteen_rates.school_class_id')
            ->join('canteen_subscriptions', 'canteen_rates.id', '=', 'canteen_subscriptions.canteen_rate_id')
            ->where('canteen_rates.school_id', $schoolId)
            ->where('canteen_rates.school_year_id', $schoolYearId);

        if ($filterMonth) {
            $query->join('canteen_installments', 'canteen_subscriptions.id', '=', 'canteen_installments.canteen_subscription_id')
                ->where('canteen_installments.month', $filterMonth)
                ->selectRaw('COALESCE(SUM(canteen_installments.amount), 0) as total_expected')
                ->selectRaw('COALESCE(SUM(canteen_installments.paid_amount), 0) as total_paid')
                ->selectRaw('COALESCE(SUM(canteen_installments.amount - canteen_installments.paid_amount), 0) as total_unpaid');
        } else {
            $query->selectRaw('COALESCE(SUM(canteen_subscriptions.total_amount), 0) as total_expected')
                ->selectRaw('COALESCE(SUM(canteen_subscriptions.paid_amount), 0) as total_paid')
                ->selectRaw('COALESCE(SUM(canteen_subscriptions.remaining_amount), 0) as total_unpaid');
        }

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
        $allClasses = SchoolClass::where('school_id', $schoolId)->orderBy('name')->get();

        $availableMonths = [
            '' => 'Tous les mois (Vue Globale)',
            '2026-09' => 'Septembre 2026', '2026-10' => 'Octobre 2026', '2026-11' => 'Novembre 2026',
            '2026-12' => 'Décembre 2026', '2027-01' => 'Janvier 2027', '2027-02' => 'Février 2027',
            '2027-03' => 'Mars 2027', '2027-04' => 'Avril 2027', '2027-05' => 'Mai 2027', '2027-06' => 'Juin 2027',
        ];

        return view('app.canteen.reports.unpaid_by_class', compact('classes', 'globalStats', 'schoolYears', 'schoolYearId', 'allClasses', 'filterClassId', 'filterMonth', 'availableMonths'));
    }


        // ==========================================
    // 1. CORRECTION DE LA LISTE DES PAIEMENTS
    // ==========================================
    public function getSubscriptionsByClass(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->query('school_year_id');
        $classId = $request->query('class_id');

        if (!$schoolYearId || !$classId) {
            return response()->json([]);
        }

        // ✅ CORRECTION MAJEURE : On ne se fie plus aveuglément à 'remaining_amount > 0'
        // On cherche les abonnements qui ont AU MOINS un mois impayé ou partiel
        $subscriptions = CanteenSubscription::where('school_id', $schoolId)
            ->where('school_year_id', $schoolYearId)
            ->whereHas('canteenRate', function($q) use ($classId) {
                $q->where('school_class_id', $classId);
            })
            ->whereHas('installments', function($q) {
                $q->where('status', '!=', 'paid'); // C'est la vraie condition : il reste des mois à payer
            })
            ->with([
                'student', 
                'canteenRate.schoolClass', 
                'installments' => function($q) {
                    $q->where('status', '!=', 'paid')->orderBy('due_date', 'asc');
                }
            ])
            ->get()
            ->map(function($sub) {
                // Recalcul dynamique du reste à payer basé sur les mois impayés pour être sûr à 100%
                $unpaidMonths = $sub->installments->map(function($inst) {
                    return \Carbon\Carbon::parse($inst->month . '-01')->translatedFormat('F Y');
                })->toArray();

                $realRemaining = $sub->installments->sum(function($inst) {
                    return $inst->amount - $inst->paid_amount;
                });

                // On met à jour la base de données si elle était désynchronisée
                if ($sub->remaining_amount != $realRemaining) {
                    $sub->remaining_amount = $realRemaining;
                    $sub->status = $realRemaining <= 0 ? 'paid' : 'active';
                    $sub->save();
                }

                return [
                    'id' => $sub->id,
                    'student_name' => $sub->student ? ($sub->student->last_name . ' ' . $sub->student->first_name) : 'Élève inconnu',
                    'matricule' => $sub->student ? $sub->student->matricule : 'N/A',
                    'remaining' => $realRemaining,
                    'class_name' => $sub->canteenRate && $sub->canteenRate->schoolClass ? $sub->canteenRate->schoolClass->name : 'N/A',
                    'unpaid_months' => $unpaidMonths
                ];
            });

        return response()->json($subscriptions);
    }

    // ==========================================
    // 2. CORRECTION DE L'INSCRIPTION (PLUS DE SILENCE)
    // ==========================================
    public function subscriptionsStore(Request $request)
    {
        $schoolId = session('current_school_id');

        $validated = $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'enrollments' => 'required|array|min:1',
            'enrollments.*.selected' => 'required|boolean',
            'enrollments.*.months' => 'required|array|min:1',
            'enrollments.*.months.*' => 'required|date_format:Y-m',
            'enrollments.*.amount' => 'required|numeric|min:0',
            'enrollments.*.payment_method' => 'required|in:cash,mobile_money,transfer,check',
        ]);

        DB::beginTransaction();
        try {
            $successCount = 0;
            $skipCount = 0;
            $errors = [];

            foreach ($validated['enrollments'] as $studentId => $data) {
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

                $rate = CanteenRate::where('school_id', $schoolId)
                    ->where('school_year_id', $validated['school_year_id'])
                    ->where('school_class_id', $enrollment->school_class_id)
                    ->first();

                if (!$rate) {
                    $errors[] = "Aucun tarif défini pour la classe de cet élève.";
                    continue;
                }

                $existing = CanteenSubscription::where('student_id', $studentId)
                    ->where('school_year_id', $validated['school_year_id'])
                    ->first();

                $newMonthsCount = count($data['months']);
                $newTotalAmount = $newMonthsCount * $rate->monthly_rate;
                $paidNow = $data['amount'];

                if ($existing) {
                    // ✅ AMÉLIORATION : Au lieu de sauter silencieusement, on MET À JOUR l'abonnement existant
                    // On ajoute les nouveaux mois au total et au reste à payer
                    $existing->total_amount += $newTotalAmount;
                    $existing->remaining_amount += $newTotalAmount; // paid_amount ne change pas
                    $existing->status = $existing->remaining_amount <= 0 ? 'paid' : 'active';
                    $existing->save();

                    $subscriptionId = $existing->id;
                } else {
                    // Création d'un nouvel abonnement
                    $subscription = CanteenSubscription::create([
                        'school_id' => $schoolId,
                        'student_id' => $studentId,
                        'school_year_id' => $validated['school_year_id'],
                        'canteen_rate_id' => $rate->id,
                        'total_amount' => $newTotalAmount,
                        'paid_amount' => $paidNow,
                        'remaining_amount' => $newTotalAmount - $paidNow,
                        'status' => ($newTotalAmount - $paidNow) <= 0 ? 'paid' : 'active',
                    ]);
                    $subscriptionId = $subscription->id;
                }

                // Création des échéances (en évitant les doublons si mise à jour)
                foreach ($data['months'] as $month) {
                    $instExists = CanteenInstallment::where('canteen_subscription_id', $subscriptionId)
                        ->where('month', $month)
                        ->exists();

                    if (!$instExists) {
                        CanteenInstallment::create([
                            'canteen_subscription_id' => $subscriptionId,
                            'month' => $month,
                            'amount' => $rate->monthly_rate,
                            'paid_amount' => 0,
                            'due_date' => Carbon::parse($month . '-01')->day(5),
                            'status' => 'pending',
                        ]);
                    }
                }

                // Traitement du paiement
                if ($paidNow > 0 && !empty($data['payment_method'])) {
                    CanteenPayment::create([
                        'school_id' => $schoolId,
                        'canteen_subscription_id' => $subscriptionId,
                        'amount' => $paidNow,
                        'payment_date' => now(),
                        'payment_method' => $data['payment_method'],
                        'payment_type' => $existing ? 'additional' : 'initial',
                        'received_by' => auth()->id(),
                    ]);

                    // Répartition FIFO sur les échéances
                    $subscription = CanteenSubscription::find($subscriptionId);
                    $installments = $subscription->installments()->orderBy('due_date', 'asc')->get();
                    $remainingPaymentToAllocate = $paidNow;

                    foreach ($installments as $inst) {
                        if ($remainingPaymentToAllocate <= 0) break;
                        $dueAmount = $inst->amount - $inst->paid_amount;

                        if ($remainingPaymentToAllocate >= $dueAmount) {
                            $inst->paid_amount = $inst->amount;
                            $inst->status = 'paid';
                            $remainingPaymentToAllocate -= $dueAmount;
                        } else {
                            $inst->paid_amount += $remainingPaymentToAllocate;
                            $inst->status = 'partial';
                            $remainingPaymentToAllocate = 0;
                        }
                        $inst->save();
                    }

                    // Recalcul final du reste à payer global
                    $totalPaid = $subscription->payments()->sum('amount');
                    $subscription->paid_amount = $totalPaid;
                    $subscription->remaining_amount = $subscription->total_amount - $totalPaid;
                    $subscription->status = $subscription->remaining_amount <= 0 ? 'paid' : 'active';
                    $subscription->save();
                }

                $successCount++;
            }

            DB::commit();

            $message = "✅ $successCount élève(s) traité(s) avec succès !";
            if ($skipCount > 0) $message .= " ($skipCount déjà totalement inscrits ignorés).";
            if (!empty($errors)) $message .= " | Erreurs: " . implode(', ', array_unique($errors));

            return redirect()->route('canteen.subscriptions.index', ['school_year_id' => $validated['school_year_id']])
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur système : ' . $e->getMessage()])->withInput();
        }
    }
}