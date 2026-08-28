<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Mail\ExtraPaymentConfirmedMail;
use App\Models\ActivityLog;
use App\Models\Enrollment;
use App\Models\Extra;
use App\Models\ExtraCategory;
use App\Models\ExtraInstallment;
use App\Models\ExtraPayment;
use App\Models\ExtraSchedule;
use App\Models\ExtraSubscription;
use App\Models\ExtraTarif;
use App\Models\NotificationLog;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\StudentInstallment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ExtraController extends Controller
{
    // ==========================================
    // CATÉGORIES
    // ==========================================

    public function categoriesIndex()
    {
        $schoolId = session('current_school_id');

        $categories = ExtraCategory::where('school_id', $schoolId)
            ->withCount('extras')
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        return view('app.extras.categories.index', compact('categories'));
    }

    public function categoriesStore(Request $request)
    {
        $schoolId = session('current_school_id');

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:10',
            'order' => 'nullable|integer|min:0',
        ]);

        $validated['school_id'] = $schoolId;
        ExtraCategory::create($validated);

        ActivityLog::logAction('extras.category.created', "Création de la catégorie d'extras « {$validated['name']} »");

        return back()->with('success', '✅ Catégorie créée avec succès !');
    }

    public function categoriesUpdate(Request $request, $id)
    {
        $category = ExtraCategory::where('school_id', session('current_school_id'))->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:10',
            'order' => 'nullable|integer|min:0',
        ]);

        $category->update($validated);

        return back()->with('success', '✅ Catégorie mise à jour avec succès !');
    }

    public function categoriesDestroy($id)
    {
        $category = ExtraCategory::where('school_id', session('current_school_id'))->findOrFail($id);

        if ($category->extras()->exists()) {
            return back()->withErrors(['error' => 'Impossible de supprimer une catégorie contenant des extras.']);
        }

        $category->delete();

        return back()->with('success', '✅ Catégorie supprimée avec succès !');
    }

    // ==========================================
    // CATALOGUE DES EXTRAS
    // ==========================================

    public function catalogueIndex(Request $request)
    {
        $schoolId = session('current_school_id');
        $categoryId = $request->get('category_id');

        $query = Extra::where('school_id', $schoolId)->with('category');

        if ($categoryId) {
            $query->where('extra_category_id', $categoryId);
        }

        $extras = $query->orderBy('name')->get();
        $categories = ExtraCategory::where('school_id', $schoolId)->orderBy('name')->get();

        return view('app.extras.catalogue.index', compact('extras', 'categories', 'categoryId'));
    }

    public function catalogueCreate()
    {
        $schoolId = session('current_school_id');

        $categories = ExtraCategory::where('school_id', $schoolId)->orderBy('name')->get();

        if ($categories->isEmpty()) {
            return redirect()->route('extras.categories.index')
                ->withErrors(['error' => 'Créez d\'abord au moins une catégorie avant d\'ajouter un extra.']);
        }

        return view('app.extras.catalogue.create', compact('categories'));
    }

    public function catalogueStore(Request $request)
    {
        $schoolId = session('current_school_id');

        $validated = $request->validate([
            'extra_category_id' => 'required|exists:extra_categories,id',
            'code' => 'required|string|max:30',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
            'target_audience' => 'nullable|string|max:100',
            'billing_type' => 'required|in:recurring,one_time',
            'capacity' => 'nullable|integer|min:1',
            'conditions' => 'nullable|string|max:1000',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location' => 'nullable|string|max:150',
            'daycare_closing_time' => 'nullable|date_format:H:i',
            'overage_rate_per_minute' => 'nullable|numeric|min:0',
        ]);

        $exists = Extra::where('school_id', $schoolId)->where('code', $validated['code'])->exists();
        if ($exists) {
            return back()->withErrors(['code' => 'Ce code est déjà utilisé par un autre extra.'])->withInput();
        }

        $validated['school_id'] = $schoolId;
        $extra = Extra::create($validated);

        ActivityLog::logAction('extras.extra.created', "Création de l'extra « {$extra->name} »");

        return redirect()->route('extras.catalogue.edit', $extra->id)
            ->with('success', '✅ Extra créé avec succès ! Ajoutez maintenant un tarif.');
    }

    public function catalogueEdit($id)
    {
        $extra = Extra::where('school_id', session('current_school_id'))->findOrFail($id);
        $categories = ExtraCategory::where('school_id', $extra->school_id)->orderBy('name')->get();
        $classes = SchoolClass::where('school_id', $extra->school_id)->orderBy('name')->get();
        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();
        $tarifs = $extra->tarifs()->with('schoolYear', 'schoolClass')->orderByDesc('school_year_id')->get();
        $schedules = $extra->schedules()->orderBy('day_of_week')->orderBy('start_time')->get();

        return view('app.extras.catalogue.edit', compact('extra', 'categories', 'classes', 'schoolYears', 'tarifs', 'schedules'));
    }

    public function catalogueUpdate(Request $request, $id)
    {
        $extra = Extra::where('school_id', session('current_school_id'))->findOrFail($id);

        $validated = $request->validate([
            'extra_category_id' => 'required|exists:extra_categories,id',
            'code' => 'required|string|max:30',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
            'target_audience' => 'nullable|string|max:100',
            'billing_type' => 'required|in:recurring,one_time',
            'capacity' => 'nullable|integer|min:1',
            'conditions' => 'nullable|string|max:1000',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location' => 'nullable|string|max:150',
            'daycare_closing_time' => 'nullable|date_format:H:i',
            'overage_rate_per_minute' => 'nullable|numeric|min:0',
            'destination' => 'nullable|string|max:150',
            'registration_deadline' => 'nullable|date',
            'includes_transport' => 'nullable|boolean',
            'requires_parental_authorization' => 'nullable|boolean',
        ]);

        $duplicate = Extra::where('school_id', $extra->school_id)
            ->where('code', $validated['code'])
            ->where('id', '!=', $extra->id)
            ->exists();
        if ($duplicate) {
            return back()->withErrors(['code' => 'Ce code est déjà utilisé par un autre extra.'])->withInput();
        }

        $validated['includes_transport'] = $request->boolean('includes_transport');
        $validated['requires_parental_authorization'] = $request->boolean('requires_parental_authorization');

        $extra->update($validated);

        ActivityLog::logAction('extras.extra.updated', "Modification de l'extra « {$extra->name} »");

        return back()->with('success', '✅ Extra mis à jour avec succès !');
    }

    public function catalogueDestroy($id)
    {
        $extra = Extra::where('school_id', session('current_school_id'))->findOrFail($id);

        if ($extra->subscriptions()->whereIn('status', ['active', 'validated', 'requested', 'pending'])->exists()) {
            return back()->withErrors(['error' => 'Impossible de supprimer un extra qui a des inscriptions en cours.']);
        }

        $extra->delete();

        return redirect()->route('extras.catalogue.index')->with('success', '✅ Extra supprimé avec succès !');
    }

    // ==========================================
    // TARIFS (sous-formulaire de la fiche extra)
    // ==========================================

    public function tarifsStore(Request $request, $extraId)
    {
        $extra = Extra::where('school_id', session('current_school_id'))->findOrFail($extraId);

        $validated = $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'school_class_id' => 'nullable|exists:school_classes,id',
            'amount' => 'required|numeric|min:0',
            'periods_count' => 'nullable|integer|min:1|max:12',
            'is_open_ended' => 'nullable|boolean',
            'start_period' => 'nullable|date_format:Y-m',
            'end_period' => 'nullable|date_format:Y-m|after_or_equal:start_period',
            'due_day' => 'nullable|integer|min:1|max:28',
            'description' => 'nullable|string|max:500',
        ]);

        $exists = ExtraTarif::where('extra_id', $extra->id)
            ->where('school_year_id', $validated['school_year_id'])
            ->where('school_class_id', $validated['school_class_id'] ?? null)
            ->exists();

        if ($exists) {
            return back()->withErrors(['school_class_id' => 'Un tarif existe déjà pour cette combinaison année/classe.']);
        }

        $validated['extra_id'] = $extra->id;
        $validated['due_day'] = $validated['due_day'] ?? 5;
        $validated['is_open_ended'] = $request->boolean('is_open_ended');
        ExtraTarif::create($validated);

        ActivityLog::logAction('extras.tarif.created', "Ajout d'un tarif pour l'extra « {$extra->name} »");

        return back()->with('success', '✅ Tarif ajouté avec succès !');
    }

    public function tarifsUpdate(Request $request, $id)
    {
        $tarif = ExtraTarif::whereHas('extra', fn ($q) => $q->where('school_id', session('current_school_id')))
            ->findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'periods_count' => 'nullable|integer|min:1|max:12',
            'is_open_ended' => 'nullable|boolean',
            'start_period' => 'nullable|date_format:Y-m',
            'end_period' => 'nullable|date_format:Y-m|after_or_equal:start_period',
            'due_day' => 'nullable|integer|min:1|max:28',
            'description' => 'nullable|string|max:500',
        ]);

        $validated['is_open_ended'] = $request->boolean('is_open_ended');
        $oldAmount = $tarif->amount;
        $tarif->update($validated);

        ActivityLog::logAction(
            'extras.tarif.updated',
            "Modification du tarif de l'extra « {$tarif->extra->name} » : {$oldAmount} FCFA → {$tarif->amount} FCFA"
        );

        return back()->with('success', '✅ Tarif mis à jour avec succès !');
    }

    public function tarifsDestroy($id)
    {
        $tarif = ExtraTarif::whereHas('extra', fn ($q) => $q->where('school_id', session('current_school_id')))
            ->findOrFail($id);

        if ($tarif->subscriptions()->exists()) {
            return back()->withErrors(['error' => 'Impossible de supprimer un tarif déjà utilisé par des inscriptions.']);
        }

        $extraId = $tarif->extra_id;
        $tarif->delete();

        return redirect()->route('extras.catalogue.edit', $extraId)->with('success', '✅ Tarif supprimé avec succès !');
    }

    // ==========================================
    // PLANNING (activités : jours/horaires)
    // ==========================================

    public function schedulesStore(Request $request, $extraId)
    {
        $extra = Extra::where('school_id', session('current_school_id'))->findOrFail($extraId);

        $validated = $request->validate([
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $validated['extra_id'] = $extra->id;
        ExtraSchedule::create($validated);

        return redirect()->route('extras.catalogue.edit', $extra->id)->with('success', '✅ Créneau ajouté avec succès !');
    }

    public function schedulesDestroy($id)
    {
        $schedule = ExtraSchedule::whereHas('extra', fn ($q) => $q->where('school_id', session('current_school_id')))->findOrFail($id);
        $extraId = $schedule->extra_id;
        $schedule->delete();

        return redirect()->route('extras.catalogue.edit', $extraId)->with('success', '✅ Créneau supprimé avec succès !');
    }

    // ==========================================
    // AJAX
    // ==========================================

    public function classesByCycle(Request $request)
    {
        $schoolId = session('current_school_id');
        $cycle = $request->query('cycle');

        $classes = SchoolClass::where('school_id', $schoolId)
            ->whereRaw('LOWER(cycle) = ?', [strtolower($cycle)])
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($classes);
    }

    public function studentsByClass(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->school_year_id ?? SchoolYear::where('school_id', $schoolId)->where('is_active', true)->value('id');

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

    public function tarifForClass(Request $request)
    {
        $extraId = $request->query('extra_id');
        $schoolYearId = $request->query('school_year_id');
        $classId = $request->query('class_id');

        $extra = Extra::where('school_id', session('current_school_id'))->findOrFail($extraId);

        $tarif = ExtraTarif::where('extra_id', $extraId)
            ->where('school_year_id', $schoolYearId)
            ->where('school_class_id', $classId)
            ->first()
            ?? ExtraTarif::where('extra_id', $extraId)
                ->where('school_year_id', $schoolYearId)
                ->whereNull('school_class_id')
                ->first();

        if (! $tarif) {
            return response()->json(null);
        }

        return response()->json([
            'id' => $tarif->id,
            'amount' => $tarif->amount,
            'periods_count' => $tarif->periods_count,
            'start_period' => $tarif->start_period,
            'end_period' => $tarif->end_period,
            'due_day' => $tarif->due_day,
            'billing_type' => $extra->billing_type,
        ]);
    }

    // ==========================================
    // INSCRIPTIONS
    // ==========================================

    public function subscriptionsIndex(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));
        $extraId = $request->get('extra_id', '');
        $status = $request->get('status', '');

        $query = ExtraSubscription::where('school_id', $schoolId)
            ->where('school_year_id', $schoolYearId)
            ->with(['student', 'extra.category', 'extraTarif.schoolClass']);

        if ($extraId) {
            $query->where('extra_id', $extraId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $subscriptions = $query->orderByDesc('created_at')->get();

        $extras = Extra::where('school_id', $schoolId)->orderBy('name')->get();
        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();

        return view('app.extras.subscriptions.index', compact(
            'subscriptions', 'extras', 'schoolYears', 'schoolYearId', 'extraId', 'status'
        ));
    }

    public function subscriptionsCreate(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));

        $extras = Extra::where('school_id', $schoolId)->where('status', 'active')->orderBy('name')->get();
        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();

        return view('app.extras.subscriptions.create', compact('extras', 'schoolYears', 'schoolYearId'));
    }

    public function subscriptionsStore(Request $request)
    {
        $schoolId = session('current_school_id');

        $validated = $request->validate([
            'extra_id' => 'required|exists:extras,id',
            'school_year_id' => 'required|exists:school_years,id',
            'enrollments' => 'required|array|min:1',
            'enrollments.*.selected' => 'required|boolean',
            'enrollments.*.periods' => 'nullable|array',
            'enrollments.*.periods.*' => 'string',
            'enrollments.*.amount' => 'required|numeric|min:0',
            'enrollments.*.payment_method' => 'nullable|in:cash,mobile_money,transfer,check',
            'enrollments.*.discount_type' => 'nullable|in:individual,family,sibling,promotion,free,scholarship,exceptional',
            'enrollments.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'enrollments.*.discount_reason' => 'nullable|string|max:255',
        ]);

        $extra = Extra::where('school_id', $schoolId)->findOrFail($validated['extra_id']);

        if (! $extra->isActive()) {
            return back()->withErrors(['error' => 'Cet extra est inactif, impossible d\'y inscrire des élèves.']);
        }

        DB::beginTransaction();
        try {
            $successCount = 0;
            $errors = [];
            $paymentsToNotify = [];

            foreach ($validated['enrollments'] as $studentId => $data) {
                if (empty($data['selected'])) {
                    continue;
                }

                if (ExtraSubscription::where('student_id', $studentId)->where('extra_id', $extra->id)
                    ->where('school_year_id', $validated['school_year_id'])->exists()) {
                    $errors[] = 'Un élève est déjà inscrit à cet extra pour cette année.';

                    continue;
                }

                $enrollment = Enrollment::where('student_id', $studentId)
                    ->where('school_year_id', $validated['school_year_id'])
                    ->first();

                if (! $enrollment) {
                    $errors[] = "L'élève n'est pas inscrit dans cette année scolaire.";

                    continue;
                }

                $tarif = ExtraTarif::where('extra_id', $extra->id)
                    ->where('school_year_id', $validated['school_year_id'])
                    ->where('school_class_id', $enrollment->school_class_id)
                    ->first()
                    ?? ExtraTarif::where('extra_id', $extra->id)
                        ->where('school_year_id', $validated['school_year_id'])
                        ->whereNull('school_class_id')
                        ->first();

                if (! $tarif) {
                    $errors[] = 'Aucun tarif défini pour la classe de cet élève.';

                    continue;
                }

                $periods = $extra->isRecurring() ? ($data['periods'] ?? []) : ['unique'];

                if ($extra->isRecurring() && empty($periods)) {
                    $errors[] = 'Sélectionnez au moins une période pour un extra périodique.';

                    continue;
                }

                $grossAmount = $extra->isRecurring()
                    ? count($periods) * $tarif->amount
                    : $tarif->amount;

                $discountType = $data['discount_type'] ?? null;
                $discountPercent = $discountType === 'free' ? 100 : max(0, min(100, (float) ($data['discount_percent'] ?? 0)));
                $discountAmount = $discountType ? round($grossAmount * $discountPercent / 100, 2) : 0;
                $totalAmount = round($grossAmount - $discountAmount, 2);
                $installmentUnitAmount = $discountType ? round($tarif->amount * (1 - $discountPercent / 100), 2) : $tarif->amount;

                // Capacité atteinte : l'élève est placé sur liste d'attente plutôt que
                // rejeté, aucune échéance n'est créée tant qu'il n'est pas promu.
                $hasCapacity = $extra->hasAvailableCapacity();
                if (! $hasCapacity) {
                    $errors[] = "🕒 Capacité maximale atteinte pour « {$extra->name} », un ou plusieurs élèves ont été placés sur liste d'attente.";
                }

                $paidNow = $hasCapacity ? min((float) $data['amount'], $totalAmount) : 0;

                $subscription = ExtraSubscription::create([
                    'school_id' => $schoolId,
                    'student_id' => $studentId,
                    'extra_id' => $extra->id,
                    'extra_tarif_id' => $tarif->id,
                    'school_year_id' => $validated['school_year_id'],
                    'total_amount' => $totalAmount,
                    'paid_amount' => 0,
                    'remaining_amount' => $totalAmount,
                    'original_amount' => $grossAmount,
                    'discount_type' => $discountType,
                    'discount_amount' => $discountAmount,
                    'discount_reason' => $data['discount_reason'] ?? null,
                    'status' => $hasCapacity ? 'active' : 'waitlisted',
                    'validated_by' => $hasCapacity ? auth()->id() : null,
                    'validated_at' => $hasCapacity ? now() : null,
                ]);

                if ($hasCapacity) {
                    foreach ($periods as $period) {
                        $dueDate = $period === 'unique'
                            ? now()
                            : Carbon::parse($period.'-01')->day(min($tarif->due_day, Carbon::parse($period.'-01')->daysInMonth));

                        ExtraInstallment::create([
                            'extra_subscription_id' => $subscription->id,
                            'period' => $period,
                            'amount' => $installmentUnitAmount,
                            'paid_amount' => 0,
                            'due_date' => $dueDate,
                            'status' => 'pending',
                        ]);
                    }
                }

                if ($paidNow > 0 && ! empty($data['payment_method'])) {
                    $payment = ExtraPayment::create([
                        'school_id' => $schoolId,
                        'extra_subscription_id' => $subscription->id,
                        'amount' => $paidNow,
                        'payment_date' => now(),
                        'payment_method' => $data['payment_method'],
                        'received_by' => auth()->id(),
                    ]);
                    // Le hook ExtraPayment::booted() recalcule paid_amount/remaining_amount/status.
                    $subscription->refresh()->allocatePayment($paidNow);
                    $paymentsToNotify[] = $payment;
                }

                $successCount++;
            }

            DB::commit();

            foreach ($paymentsToNotify ?? [] as $payment) {
                $this->sendPaymentConfirmation($payment);
            }

            $message = "✅ $successCount élève(s) inscrit(s) avec succès !";
            if (! empty($errors)) {
                $message .= ' | ⚠️ '.implode(' ', array_unique($errors));
            }

            ActivityLog::logAction('extras.subscription.created', "Inscription de {$successCount} élève(s) à l'extra « {$extra->name} »");

            return redirect()->route('extras.subscriptions.index', ['school_year_id' => $validated['school_year_id']])
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Erreur système : '.$e->getMessage()])->withInput();
        }
    }

    public function subscriptionsDestroy($id)
    {
        $subscription = ExtraSubscription::where('school_id', session('current_school_id'))->findOrFail($id);

        if ($subscription->paid_amount > 0) {
            return back()->withErrors(['error' => 'Impossible d\'annuler une inscription pour laquelle un paiement a déjà été enregistré. Suspendez-la plutôt.']);
        }

        $schoolYearId = $subscription->school_year_id;
        $subscription->delete();

        return redirect()->route('extras.subscriptions.index', ['school_year_id' => $schoolYearId])
            ->with('success', '✅ Inscription annulée avec succès !');
    }

    /**
     * Valide (accepte) ou refuse une demande d'inscription faite par un parent.
     */
    public function subscriptionsValidate(Request $request, $id)
    {
        $subscription = ExtraSubscription::where('school_id', session('current_school_id'))
            ->whereIn('status', ['requested', 'pending'])
            ->findOrFail($id);

        $validated = $request->validate([
            'decision' => 'required|in:accept,refuse',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validated['decision'] === 'accept') {
            if ($subscription->extra->hasAvailableCapacity()) {
                $subscription->status = 'active';
            } else {
                // Plus de place entre-temps : liste d'attente plutôt que refus. Les
                // échéances déjà créées à la demande (non payées) sont annulées, elles
                // seront recréées à la promotion depuis la liste d'attente.
                $subscription->installments()->where('paid_amount', 0)->delete();
                $subscription->status = 'waitlisted';
            }
        } else {
            $subscription->status = 'terminated';
        }

        $subscription->validated_by = auth()->id();
        $subscription->validated_at = now();
        if (! empty($validated['notes'])) {
            $subscription->notes = $validated['notes'];
        }
        $subscription->save();

        $decisionLabel = match (true) {
            $subscription->status === 'active' => 'acceptée',
            $subscription->status === 'waitlisted' => 'placée sur liste d\'attente (capacité atteinte)',
            default => 'refusée',
        };

        ActivityLog::logAction(
            'extras.subscription.'.$validated['decision'],
            "Demande d'inscription de {$subscription->student->first_name} {$subscription->student->last_name} à « {$subscription->extra->name} » : {$decisionLabel}"
        );

        $successMessage = match (true) {
            $subscription->status === 'active' => '✅ Demande acceptée !',
            $subscription->status === 'waitlisted' => "🕒 « {$subscription->extra->name} » est complet : l'élève a été placé sur liste d'attente.",
            default => '✅ Demande refusée.',
        };

        return back()->with('success', $successMessage);
    }

    /**
     * Promeut une inscription en liste d'attente vers le statut actif dès qu'une
     * place se libère (à déclencher manuellement par l'administration).
     */
    public function subscriptionsPromote($id)
    {
        $subscription = ExtraSubscription::where('school_id', session('current_school_id'))
            ->where('status', 'waitlisted')
            ->with('extra', 'extraTarif', 'student')
            ->findOrFail($id);

        if (! $subscription->extra->hasAvailableCapacity()) {
            return back()->withErrors(['error' => "⚠️ Aucune place disponible pour « {$subscription->extra->name} » pour le moment."]);
        }

        $subscription->extraTarif->createDefaultInstallmentsFor($subscription);

        $subscription->status = 'active';
        $subscription->validated_by = auth()->id();
        $subscription->validated_at = now();
        $subscription->save();

        ActivityLog::logAction(
            'extras.subscription.promoted',
            "Promotion depuis la liste d'attente de {$subscription->student->first_name} {$subscription->student->last_name} pour « {$subscription->extra->name} »"
        );

        return back()->with('success', "✅ « {$subscription->extra->name} » activé pour {$subscription->student->first_name} {$subscription->student->last_name} !");
    }

    /**
     * Marque (ou démarque) l'autorisation parentale d'une sortie scolaire comme reçue,
     * une fois le coupon papier signé rapporté par l'élève (spec §23).
     */
    public function subscriptionsToggleAuthorization($id)
    {
        $subscription = ExtraSubscription::where('school_id', session('current_school_id'))
            ->with('extra', 'student')
            ->findOrFail($id);

        $subscription->parental_authorization_signed = ! $subscription->parental_authorization_signed;
        $subscription->parental_authorization_signed_at = $subscription->parental_authorization_signed ? now() : null;
        $subscription->save();

        ActivityLog::logAction(
            'extras.subscription.authorization_toggled',
            "Autorisation parentale pour {$subscription->student->first_name} {$subscription->student->last_name} ({$subscription->extra->name}) : ".($subscription->parental_authorization_signed ? 'marquée reçue' : 'annulée')
        );

        return back()->with('success', $subscription->parental_authorization_signed ? '✅ Autorisation marquée comme reçue.' : 'Autorisation annulée.');
    }

    /**
     * Coupon d'autorisation de sortie à imprimer et faire signer par le parent (spec §24).
     */
    public function subscriptionsAuthorizationPdf($id)
    {
        $subscription = ExtraSubscription::where('school_id', session('current_school_id'))
            ->with('extra', 'student.school')
            ->findOrFail($id);

        $pdf = Pdf::loadView('pdf.extra-outing-authorization', [
            'subscription' => $subscription,
            'student' => $subscription->student,
            'extra' => $subscription->extra,
            'school' => $subscription->student->school,
        ]);

        return $pdf->download('Autorisation_Sortie_'.str_pad($subscription->id, 6, '0', STR_PAD_LEFT).'.pdf');
    }

    // ==========================================
    // PAIEMENTS
    // ==========================================

    public function paymentsIndex(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));
        $extraId = $request->get('extra_id', '');

        $query = ExtraPayment::where('school_id', $schoolId)
            ->whereHas('subscription', fn ($q) => $q->where('school_year_id', $schoolYearId))
            ->with(['subscription.student', 'subscription.extra', 'receivedByUser'])
            ->orderByDesc('payment_date');

        if ($extraId) {
            $query->whereHas('subscription', fn ($q) => $q->where('extra_id', $extraId));
        }

        $payments = $query->paginate(20)->withQueryString();

        $extras = Extra::where('school_id', $schoolId)->orderBy('name')->get();
        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();

        // Abonnements avec un reste à payer, pour le formulaire d'enregistrement de paiement.
        $unpaidSubscriptions = ExtraSubscription::where('school_id', $schoolId)
            ->where('school_year_id', $schoolYearId)
            ->where('remaining_amount', '>', 0)
            ->whereIn('status', ['active'])
            ->with(['student', 'extra'])
            ->get();

        return view('app.extras.payments.index', compact(
            'payments', 'extras', 'schoolYears', 'schoolYearId', 'extraId', 'unpaidSubscriptions'
        ));
    }

    public function paymentsStore(Request $request)
    {
        $schoolId = session('current_school_id');

        $validated = $request->validate([
            'extra_subscription_id' => 'required|exists:extra_subscriptions,id',
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,mobile_money,transfer,check',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $subscription = ExtraSubscription::where('school_id', $schoolId)->findOrFail($validated['extra_subscription_id']);

        if ($validated['amount'] > $subscription->remaining_amount) {
            return back()->withErrors(['amount' => 'Le montant dépasse le reste à payer ('.number_format($subscription->remaining_amount, 0, ',', ' ').' FCFA).'])->withInput();
        }

        DB::beginTransaction();
        try {
            $payment = ExtraPayment::create([
                'school_id' => $schoolId,
                'extra_subscription_id' => $subscription->id,
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['reference'] ?? null,
                'received_by' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
            ]);
            // Le hook ExtraPayment::booted() recalcule paid_amount/remaining_amount/status.
            $subscription->refresh()->allocatePayment($validated['amount']);

            DB::commit();

            $this->sendPaymentConfirmation($payment);

            ActivityLog::logAction(
                'extras.payment.created',
                'Paiement de '.number_format($validated['amount'], 0, ',', ' ')." FCFA pour « {$subscription->extra->name} » (élève : {$subscription->student->first_name} {$subscription->student->last_name})"
            );

            return redirect()->route('extras.payments.index', ['school_year_id' => $subscription->school_year_id])
                ->with('success', '✅ Paiement enregistré avec succès !');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Erreur : '.$e->getMessage()]);
        }
    }

    public function paymentsReceipt(ExtraPayment $payment)
    {
        if ($payment->school_id !== session('current_school_id')) {
            abort(403);
        }

        $payment->load(['subscription.student', 'subscription.extra', 'subscription.schoolYear', 'receivedByUser']);
        $school = $payment->subscription->school;

        $pdf = Pdf::loadView('pdf.extra-receipt', [
            'payment' => $payment,
            'subscription' => $payment->subscription,
            'student' => $payment->subscription->student,
            'extra' => $payment->subscription->extra,
            'school' => $school,
        ]);

        $filename = 'Recu_Extra_'.str_pad($payment->id, 6, '0', STR_PAD_LEFT).'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Envoie l'email de confirmation de paiement au parent et journalise l'envoi
     * (NotificationLog). Best-effort : un échec d'envoi ne doit jamais faire
     * échouer l'enregistrement du paiement, déjà commité à ce stade.
     */
    private function sendPaymentConfirmation(ExtraPayment $payment): void
    {
        $payment->loadMissing('subscription.student', 'subscription.extra');
        $student = $payment->subscription->student;

        if (empty($student->guardian_email)) {
            return;
        }

        $status = 'sent';
        $errorMessage = null;

        try {
            Mail::to($student->guardian_email)->send(new ExtraPaymentConfirmedMail($student, $payment));
        } catch (\Exception $e) {
            $status = 'failed';
            $errorMessage = $e->getMessage();
        }

        NotificationLog::create([
            'school_id' => $payment->school_id,
            'student_id' => $student->id,
            'type' => 'email',
            'category' => 'extra_payment_confirmed',
            'recipient_email' => $student->guardian_email,
            'message' => 'Confirmation de paiement extra : '.number_format($payment->amount, 0, ',', ' ')." FCFA pour « {$payment->subscription->extra->name} »",
            'status' => $status,
            'error_message' => $errorMessage,
        ]);
    }

    // ==========================================
    // RAPPORTS
    // ==========================================

    /**
     * Abonnements avec un reste à payer pour l'année scolaire filtrée, utilisé par
     * la page « Impayés » et son export PDF.
     */
    private function unpaidSubscriptionsQuery(int $schoolId, int $schoolYearId, $extraId)
    {
        $query = ExtraSubscription::where('extra_subscriptions.school_id', $schoolId)
            ->where('extra_subscriptions.school_year_id', $schoolYearId)
            ->where('extra_subscriptions.remaining_amount', '>', 0)
            ->with(['student', 'extra.category']);

        if ($extraId) {
            $query->where('extra_id', $extraId);
        }

        return $query->orderByDesc('remaining_amount')->get();
    }

    public function reportsUnpaid(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));
        $extraId = $request->get('extra_id', '');

        $unpaid = $this->unpaidSubscriptionsQuery($schoolId, $schoolYearId, $extraId);

        $globalStats = (object) [
            'families_count' => $unpaid->pluck('student_id')->unique()->count(),
            'total_unpaid' => $unpaid->sum('remaining_amount'),
        ];

        $extras = Extra::where('school_id', $schoolId)->orderBy('name')->get();
        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();

        return view('app.extras.reports.unpaid', compact('unpaid', 'globalStats', 'extras', 'schoolYears', 'schoolYearId', 'extraId'));
    }

    public function reportsUnpaidPdf(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));
        $extraId = $request->get('extra_id', '');

        $unpaid = $this->unpaidSubscriptionsQuery($schoolId, $schoolYearId, $extraId);

        $globalStats = (object) [
            'families_count' => $unpaid->pluck('student_id')->unique()->count(),
            'total_unpaid' => $unpaid->sum('remaining_amount'),
        ];

        $school = School::find($schoolId);
        $schoolYear = SchoolYear::find($schoolYearId);

        $pdf = Pdf::loadView('pdf.extra-unpaid-report', compact('unpaid', 'globalStats', 'school', 'schoolYear'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('Impayes_Extras_'.now()->format('Y-m-d').'.pdf');
    }

    public function subscriptionsPdf(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));
        $extraId = $request->get('extra_id', '');
        $status = $request->get('status', '');

        $query = ExtraSubscription::where('school_id', $schoolId)
            ->where('school_year_id', $schoolYearId)
            ->with(['student', 'extra.category', 'extraTarif.schoolClass']);

        if ($extraId) {
            $query->where('extra_id', $extraId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $subscriptions = $query->orderBy('extra_id')
            ->get()
            ->sortBy(fn ($s) => $s->student->last_name.$s->student->first_name);

        $school = School::find($schoolId);
        $schoolYear = SchoolYear::find($schoolYearId);
        $extraName = $extraId ? Extra::find($extraId)?->name : 'Tous les extras';

        $pdf = Pdf::loadView('pdf.extra-subscriptions-list', compact('subscriptions', 'school', 'schoolYear', 'extraName'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('Liste_Inscrits_Extras_'.now()->format('Y-m-d').'.pdf');
    }

    public function dashboard(Request $request)
    {
        $schoolId = session('current_school_id');
        $schoolYearId = $request->get('school_year_id', SchoolYear::where('is_active', true)->value('id'));

        $subscriptions = ExtraSubscription::where('school_id', $schoolId)
            ->where('school_year_id', $schoolYearId)
            ->whereIn('status', ['active', 'completed', 'suspended'])
            ->with('extra')
            ->get();

        $kpis = (object) [
            'active_extras_count' => Extra::where('school_id', $schoolId)->where('status', 'active')->count(),
            'subscribed_students_count' => $subscriptions->pluck('student_id')->unique()->count(),
            'total_invoiced' => $subscriptions->sum('total_amount'),
            'total_collected' => $subscriptions->sum('paid_amount'),
            'total_unpaid' => $subscriptions->sum('remaining_amount'),
            'unpaid_count' => $subscriptions->where('remaining_amount', '>', 0)->count(),
        ];

        $byExtra = $subscriptions->groupBy('extra.name')->map(function ($group) {
            return (object) [
                'name' => $group->first()->extra->name,
                'students_count' => $group->count(),
                'total_invoiced' => $group->sum('total_amount'),
                'total_collected' => $group->sum('paid_amount'),
            ];
        })->sortByDesc('total_collected')->values();

        $kpis->payment_rate = $kpis->total_invoiced > 0
            ? round(($kpis->total_collected / $kpis->total_invoiced) * 100, 1)
            : 0;

        // CA encaissé par mois (12 derniers mois glissants), toutes années scolaires
        // confondues sur la période : un paiement encaissé en août reste visible même
        // si l'année scolaire sélectionnée a changé entre-temps.
        $monthlyPayments = ExtraPayment::where('extra_payments.school_id', $schoolId)
            ->where('payment_date', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as ym, SUM(amount) as total")
            ->groupBy('ym')
            ->orderBy('ym')
            ->pluck('total', 'ym');

        $monthlyRevenue = collect(range(11, 0))->map(function ($offset) use ($monthlyPayments) {
            $month = now()->subMonths($offset);
            $key = $month->format('Y-m');

            return (object) [
                'label' => $month->translatedFormat('M Y'),
                'total' => (float) ($monthlyPayments[$key] ?? 0),
            ];
        });

        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();

        return view('app.extras.dashboard', compact('kpis', 'byExtra', 'monthlyRevenue', 'schoolYears', 'schoolYearId'));
    }

    // ==========================================
    // FACTURE CONSOLIDÉE (scolarité + tous les extras d'un élève sur un mois)
    // ==========================================

    public function consolidatedInvoiceIndex(Request $request)
    {
        $schoolId = session('current_school_id');
        $studentId = $request->get('student_id', '');
        $month = $request->get('month', now()->format('Y-m'));

        $students = Student::where('school_id', $schoolId)->orderBy('last_name')->orderBy('first_name')->get(['id', 'matricule', 'first_name', 'last_name']);

        $data = $studentId ? $this->buildConsolidatedInvoiceData($schoolId, $studentId, $month) : null;

        return view('app.extras.invoices.consolidated', array_merge(
            compact('students', 'studentId', 'month'),
            $data ?? ['student' => null, 'lines' => collect(), 'totals' => null]
        ));
    }

    public function consolidatedInvoicePdf(Request $request)
    {
        $schoolId = session('current_school_id');
        $studentId = $request->get('student_id');
        $month = $request->get('month', now()->format('Y-m'));

        $data = $this->buildConsolidatedInvoiceData($schoolId, $studentId, $month);
        $school = School::find($schoolId);

        $pdf = Pdf::loadView('pdf.extra-consolidated-invoice', array_merge($data, compact('school', 'month')));

        return $pdf->download('Facture_'.str_pad($studentId, 6, '0', STR_PAD_LEFT).'_'.$month.'.pdf');
    }

    /**
     * Regroupe sur un seul document, pour un élève et un mois donnés, les échéances
     * de scolarité (StudentInstallment) et de tous les extras souscrits
     * (ExtraInstallment) dues ce mois-là (spec §14 — la facture reste réglée service
     * par service en coulisses, ceci n'est qu'une vue consolidée pour impression).
     */
    private function buildConsolidatedInvoiceData(int $schoolId, int $studentId, string $month): array
    {
        $student = Student::where('school_id', $schoolId)->findOrFail($studentId);
        $monthStart = $month.'-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        $lines = collect();

        StudentInstallment::whereHas('enrollment', fn ($q) => $q->where('student_id', $studentId))
            ->whereBetween('due_date', [$monthStart, $monthEnd])
            ->get()
            ->each(function ($installment) use ($lines) {
                $lines->push((object) [
                    'service' => 'Scolarité — '.$installment->description,
                    'amount' => (float) $installment->amount,
                    'paid' => (float) $installment->paid_amount,
                    'remaining' => (float) ($installment->amount - $installment->paid_amount),
                    'due_date' => $installment->due_date,
                ]);
            });

        ExtraInstallment::whereHas('subscription', fn ($q) => $q->where('school_id', $schoolId)->where('student_id', $studentId))
            ->whereBetween('due_date', [$monthStart, $monthEnd])
            ->with('subscription.extra')
            ->get()
            ->each(function ($installment) use ($lines) {
                $lines->push((object) [
                    'service' => $installment->subscription->extra->name,
                    'amount' => (float) $installment->amount,
                    'paid' => (float) $installment->paid_amount,
                    'remaining' => (float) ($installment->amount - $installment->paid_amount),
                    'due_date' => $installment->due_date,
                ]);
            });

        $lines = $lines->sortBy('due_date')->values();

        $totals = (object) [
            'amount' => $lines->sum('amount'),
            'paid' => $lines->sum('paid'),
            'remaining' => $lines->sum('remaining'),
        ];

        return ['student' => $student, 'lines' => $lines, 'totals' => $totals];
    }
}
