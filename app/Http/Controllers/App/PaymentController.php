<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\StudentInstallment; // AJOUTÉ
use App\Services\StudentCardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon; // AJOUTÉ
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $schoolId = session('current_school_id');

        $query = Payment::where('school_id', $schoolId)
            ->with(['enrollment.student', 'receivedBy', 'studentInstallment', 'enrollment.schoolClass']);

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        // ✅ AJOUT DU FILTRE PAR CLASSE
        if ($request->filled('class_id')) {
            $query->whereHas('enrollment', function ($q) use ($request) {
                $q->where('school_class_id', $request->class_id);
            });
        }

        $payments = $query->orderBy('payment_date', 'desc')->paginate(15);
        $payments->appends($request->query());

        // ✅ AJOUT : Récupérer les classes pour le menu déroulant
        $classes = SchoolClass::where('school_id', $schoolId)->orderBy('name')->get();

        return view('app.payments.index', compact('payments', 'classes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $schoolId = session('current_school_id');
        $school = session('current_school');

        // 1. Charger TOUTES les années et TOUTES les classes sans condition
        $schoolYears = SchoolYear::where('school_id', $schoolId)->orderBy('start_date', 'desc')->get();
        $classes = SchoolClass::where('school_id', $schoolId)->orderBy('name')->get(); // C'est cette ligne qui est cruciale

        // 2. Définir les cycles
        $cycles = [];
        if ($school && ($school->isMaternelle() || $school->isBoth())) {
            $cycles['maternelle'] = 'Maternelle';
        }
        if ($school && ($school->isPrimaire() || $school->isBoth())) {
            $cycles['primaire'] = 'Primaire';
        }

        $selectedYearId = $request->get('school_year_id');
        $selectedCycle = $request->get('cycle');
        $selectedClassId = $request->get('class_id');
        $selectedStudentId = $request->get('student_id');
        $selectedPaymentType = $request->get('payment_type');

        $students = collect();
        $selectedEnrollment = null;
        $pendingInstallments = collect();

        // 3. Si un élève est sélectionné, on charge ses données
        if ($selectedStudentId && $selectedYearId) {
            $selectedEnrollment = Enrollment::where('student_id', $selectedStudentId)
                ->where('school_year_id', $selectedYearId)
                ->first();

            if ($selectedEnrollment) {
                // Charger les élèves de la même classe pour le select
                $students = Student::where('school_id', $schoolId)
                    ->where('status', 'active')
                    ->whereHas('classes', function ($q) use ($selectedEnrollment) {
                        $q->where('school_classes.id', $selectedEnrollment->school_class_id);
                    })
                    ->orderBy('last_name')
                    ->get();

                $pendingInstallments = StudentInstallment::where('enrollment_id', $selectedEnrollment->id)
                    ->whereIn('status', ['pending', 'partial'])
                    ->orderBy('due_date', 'asc')
                    ->get();
            }
        }

        return view('app.payments.create', compact(
            'schoolYears', 'cycles', 'classes', 'students',
            'selectedYearId', 'selectedCycle', 'selectedClassId',
            'selectedStudentId', 'selectedPaymentType', 'selectedEnrollment', 'pendingInstallments'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validation des données du formulaire
        $validatedData = $request->validate([
            'student_id' => 'required|exists:students,id',
            'enrollment_id' => 'required|exists:enrollments,id',
            'student_installment_id' => 'required|exists:student_installments,id',
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'payment_type' => 'required|string|in:registration,tuition',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // 2. Récupération des modèles liés, cloisonnée à l'école courante (StudentPolicy,
        // EnrollmentPolicy, StudentInstallmentPolicy) pour empêcher qu'un utilisateur
        // soumette les IDs d'un élève/échéance d'une autre école.
        $student = Student::findOrFail($validatedData['student_id']);
        $enrollment = Enrollment::findOrFail($validatedData['enrollment_id']);
        $installment = StudentInstallment::findOrFail($validatedData['student_installment_id']);

        abort_unless($request->user()->can('view', $student), 404);
        abort_unless($request->user()->can('view', $enrollment), 404);
        abort_unless($request->user()->can('view', $installment), 404);

        // Récupération de l'école et de l'utilisateur (Nettoyé des doublons)
        $school = $enrollment->school ?? $student->school;
        $user = auth()->user();
        $userName = $user->name ?? trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?? $user->email ?? 'Administrateur';

        // 3. Mise à jour de l'échéance (Montant payé et Statut)
        $totalAmount = $installment->amount;
        $remainingBeforePayment = $totalAmount - ($installment->paid_amount ?? 0);

        // Un paiement ne peut pas dépasser le solde de l'échéance sélectionnée : au-delà, l'excédent
        // était jusqu'ici absorbé silencieusement dans cette échéance sans être reporté sur les
        // suivantes, ce qui faussait leur statut. On demande un paiement séparé par échéance.
        if ($validatedData['amount'] > $remainingBeforePayment) {
            return back()->withErrors([
                'amount' => 'Le montant saisi ('.number_format($validatedData['amount'], 0, ',', ' ').' FCFA) dépasse le solde restant de cette échéance ('.number_format($remainingBeforePayment, 0, ',', ' ').' FCFA). Enregistrez un paiement séparé pour chaque échéance.',
            ])->withInput();
        }

        $newPaidAmount = ($installment->paid_amount ?? 0) + $validatedData['amount'];

        $installmentStatus = 'pending';
        if ($newPaidAmount >= $totalAmount) {
            $installmentStatus = 'paid';
        } elseif ($newPaidAmount > 0) {
            $installmentStatus = 'partial';
        }

        $installment->update([
            'paid_amount' => $newPaidAmount,
            'status' => $installmentStatus,
        ]);

        // 4. Création de l'enregistrement du paiement
        $payment = Payment::create([
            'school_id' => $school->id,
            'enrollment_id' => $validatedData['enrollment_id'],
            'student_installment_id' => $validatedData['student_installment_id'],
            'amount' => $validatedData['amount'],
            'payment_date' => $validatedData['payment_date'],
            'payment_method' => $validatedData['payment_method'],
            'payment_type' => $validatedData['payment_type'],
            'reference' => $validatedData['reference'] ?? null,
            'notes' => $validatedData['notes'] ?? null,
            'received_by' => auth()->id(),
        ]);

        // Variable pour stocker le chemin de la carte (sera null si ce n'est pas une inscription)
        $cardPath = null;

        // 5. ✅ RÈGLE SPÉCIALE : Si c'est un paiement d'inscription
        if ($validatedData['payment_type'] === 'registration') {
            $student->update(['status' => 'active']);

            // Récupérer le nom de la classe directement depuis l'inscription en cours de paiement
            $currentClassName = $enrollment->schoolClass->name ?? 'Non assignée';

            // QR chiffré (identifiant élève, pas de donnée lisible) et généré localement :
            // avant, la carte encodait le matricule en clair et l'image était demandée à une
            // API externe (api.qrserver.com), ce qui fuitait le matricule à un tiers.
            $cardPath = app(StudentCardService::class)->generate($student, $school, $currentClassName);
        }
        // 6. Récupérer les échéances restantes pour le reçu
        $pendingInstallments = StudentInstallment::where('enrollment_id', $validatedData['enrollment_id'])
            ->where('status', '!=', 'paid')
            ->orderBy('due_date', 'asc')
            ->get();

        // 7. ✅ GÉNÉRATION DU REÇU DE PAIEMENT
        $receiptPdf = Pdf::loadView('pdf.receipt', [
            'payment' => $payment,
            'student' => $student,
            'school' => $school,
            'schoolClass' => $enrollment->schoolClass ?? null,
            'schoolYear' => $enrollment->schoolYear ?? null,
            'userName' => $userName,
            'pendingInstallments' => $pendingInstallments,
        ]);

        $receiptFileName = 'recu_'.$payment->id.'_'.time().'.pdf';
        $receiptPath = 'receipts/'.$receiptFileName;
        Storage::disk('public')->put($receiptPath, $receiptPdf->output());

        // Sauvegarder le chemin du reçu dans la table payments (UNE SEULE FOIS)
        $payment->update(['receipt_path' => $receiptPath]);

        // 8. ✅ REDIRECTION AVEC LES LIENS DE TÉLÉCHARGEMENT (Reçu + Carte)
        $receiptUrl = asset('storage/'.$receiptPath);

        $successMessage = '
            <div class="font-semibold mb-2">✅ Paiement enregistré avec succès !</div>
            <div class="flex flex-col gap-2 text-sm">
                <a href="'.$receiptUrl.'" target="_blank" class="flex items-center text-blue-700 hover:text-blue-900 font-bold underline">
                    📥 Télécharger le Reçu de Paiement
                </a>';

        // Si une carte a été générée (paiement d'inscription), on ajoute le lien
        if ($cardPath) {
            $cardUrl = asset('storage/'.$cardPath);
            $successMessage .= '
                <a href="'.$cardUrl.'" target="_blank" class="flex items-center text-green-700 hover:text-green-900 font-bold underline">
                    🪪 Télécharger la Carte Scolaire (avec QR Code)
                </a>';
        }

        $successMessage .= '</div>';

        return redirect()->route('app.payments.index')
            ->with('payment_success', [
                'receipt_url' => $receiptUrl,
                'card_url' => $cardPath ? asset('storage/'.$cardPath) : null,
            ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        abort_unless(auth()->user()->can('view', $payment), 403);
        $payment->load(['enrollment.student', 'receivedBy', 'studentInstallment']);

        return view('app.payments.show', compact('payment'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        abort_unless(auth()->user()->can('view', $payment), 403);

        DB::beginTransaction();
        try {
            // Si le paiement était lié à une échéance, on "rembourse" virtuellement cette échéance
            if ($payment->student_installment_id) {
                $installment = StudentInstallment::find($payment->student_installment_id);
                if ($installment) {
                    $installment->paid_amount -= $payment->amount;

                    if ($installment->paid_amount <= 0) {
                        $installment->paid_amount = 0;
                        $installment->status = 'pending';
                    } else {
                        $installment->status = 'partial';
                    }
                    $installment->save();
                }
            }

            $payment->delete();
            DB::commit();

            return redirect()->route('app.payments.index')
                ->with('success', 'Paiement supprimé et échéance mise à jour !');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Erreur : '.$e->getMessage()]);
        }
    }

    /**
     * Afficher et télécharger le reçu de paiement en PDF
     */
    public function receipt(Payment $payment)
    {
        abort_unless(auth()->user()->can('view', $payment), 403);

        $payment->load([
            'enrollment.student',
            'enrollment.schoolClass',
            'enrollment.schoolYear',
            'school',
            'studentInstallment',
        ]);

        // Récupérer TOUTES les échéances encore en attente pour cette inscription
        $pendingInstallments = StudentInstallment::where('enrollment_id', $payment->enrollment_id)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('due_date', 'asc')
            ->get();

        // DÉBOGAGE : Vérifier ce qui est récupéré
        \Log::info('Pending Installments for Payment #'.$payment->id, [
            'enrollment_id' => $payment->enrollment_id,
            'count' => $pendingInstallments->count(),
            'installments' => $pendingInstallments->toArray(),
        ]);

        $student = $payment->enrollment->student;
        $schoolClass = $payment->enrollment->schoolClass;
        $schoolYear = $payment->enrollment->schoolYear;
        $school = $payment->school;

        $pdf = Pdf::loadView('pdf.receipt', compact(
            'payment', 'student', 'schoolClass', 'schoolYear', 'school', 'pendingInstallments'
        ));

        $filename = 'Recu_Paiement_'.str_pad($payment->id, 6, '0', STR_PAD_LEFT).'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Retourne la liste des élèves d'une classe avec leurs échéances (Auto-génération si manquantes)
     */
    public function getStudentsByClass(Request $request)
    {
        try {
            $schoolId = session('current_school_id');
            $classId = $request->class_id;
            $yearId = $request->year_id;

            if (! $classId || ! $yearId) {
                return response()->json([]);
            }

            // 1. Récupérer les inscriptions pour cette classe ET cette année
            $enrollments = Enrollment::where('school_id', $schoolId)
                ->where('school_year_id', $yearId)
                ->where('school_class_id', $classId)
                ->with('student')
                ->get();

            $formattedStudents = collect();

            foreach ($enrollments as $enrollment) {
                $student = $enrollment->student;
                if (! $student) {
                    continue;
                }

                // 2. Vérifier si des échéances existent déjà
                $installments = StudentInstallment::where('enrollment_id', $enrollment->id)
                    ->whereIn('status', ['pending', 'partial', 'overdue'])
                    ->orderBy('due_date', 'asc')
                    ->get();

                // 3. AUTO-GÉNÉRATION : Si aucune échéance n'existe, on les crée maintenant à la volée
                if ($installments->isEmpty()) {
                    $schoolClass = SchoolClass::find($enrollment->school_class_id);

                    if ($schoolClass && $schoolClass->total_tuition > 0) {
                        // Même générateur que StudentController::generateFeeSchedule() — voir
                        // StudentInstallment::generateScheduleFor() pour ne jamais faire diverger
                        // ancrage de date et calcul des échéances entre les deux appelants.
                        StudentInstallment::generateScheduleFor($enrollment, $schoolClass, $enrollment->enrollment_date);

                        // Recharger les échéances fraîchement générées
                        $installments = StudentInstallment::where('enrollment_id', $enrollment->id)
                            ->whereIn('status', ['pending', 'partial', 'overdue'])
                            ->orderBy('due_date', 'asc')
                            ->get();
                    }
                }

                // 4. Formater les données pour le frontend
                $formattedStudents->push([
                    'id' => $student->id,
                    'matricule' => $student->matricule,
                    'name' => trim($student->last_name.' '.$student->first_name),
                    'enrollment_id' => $enrollment->id,
                    'installments' => $installments->map(function ($inst) {
                        return [
                            'id' => $inst->id,
                            'description' => $inst->description,
                            'due_date' => Carbon::parse($inst->due_date)->format('d/m/Y'),
                            'amount' => (float) $inst->amount,
                            'paid_amount' => (float) $inst->paid_amount,
                            'remaining' => (float) ($inst->amount - $inst->paid_amount),
                            'status' => $inst->status,
                            'type' => $inst->type, // <-- 🎯 AJOUT CRUCIAL ICI
                        ];
                    }),
                ]);
            }

            return response()->json($formattedStudents);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    /**
     * Exporter la liste des paiements en format CSV (Compatible Excel)
     */
    public function export(Request $request)
    {
        $schoolId = session('current_school_id');

        // 1. Reproduire la même requête que la méthode index avec les filtres
        $query = Payment::where('school_id', $schoolId)
            ->with(['enrollment.student', 'enrollment.schoolClass', 'studentInstallment', 'receivedBy']);

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }
        // ✅ AJOUT DU FILTRE PAR CLASSE POUR L'EXPORT DES PAIEMENTS
        if ($request->filled('class_id')) {
            $query->whereHas('enrollment', function ($q) use ($request) {
                $q->where('school_class_id', $request->class_id);
            });
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        $payments = $query->orderBy('payment_date', 'desc')->get();

        // 2. Nom du fichier avec la date du jour
        $filename = 'export_paiements_'.date('Y-m-d_His').'.csv';

        // 3. En-têtes HTTP pour forcer le téléchargement
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        // 4. Génération du flux CSV
        $callback = function () use ($payments) {
            $file = fopen('php://output', 'w');

            // ✅ BOM UTF-8 : Indispensable pour qu'Excel lise correctement les accents (é, à, ô)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // En-têtes des colonnes
            fputcsv($file, [
                'Date du paiement',
                'Nom de l\'élève',
                'Classe',
                'Type',
                'Motif / Échéance',
                'Montant (FCFA)',
                'Méthode',
                'Reçu par',
            ]);

            // Données
            foreach ($payments as $payment) {
                $studentName = trim(($payment->enrollment->student->last_name ?? '').' '.($payment->enrollment->student->first_name ?? ''));
                $className = $payment->enrollment->schoolClass->name ?? 'N/A';
                $type = $payment->payment_type === 'registration' ? 'Inscription' : 'Scolarité';
                $motif = $payment->studentInstallment->description ?? 'Paiement divers';
                $receivedBy = $payment->receivedBy->name ?? ($payment->receivedBy->email ?? 'Système');

                fputcsv($file, [
                    Carbon::parse($payment->payment_date)->format('d/m/Y'),
                    $studentName,
                    $className,
                    $type,
                    $motif,
                    $payment->amount,
                    ucfirst($payment->payment_method ?? 'Espèces'),
                    $receivedBy,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
