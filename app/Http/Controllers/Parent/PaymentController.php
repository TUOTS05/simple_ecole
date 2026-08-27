<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\StudentInstallment;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentController extends Controller
{
    /**
     * Liste des paiements et échéances d'un élève
     */
    public function index($studentId)
    {
        $parent = auth()->user();

        // 1. Vérifier l'accès : l'enfant doit appartenir à ce parent
        $student = $parent->children()
            ->where('students.id', $studentId)
            ->with('school')
            ->firstOrFail();

        // ✅ AJOUT : Récupérer TOUS les enfants pour le menu déroulant
        $siblings = $parent->children()->get();

        // 2. Récupérer l'inscription de l'élève pour l'année active de SON école (un parent peut
        // avoir des enfants dans des écoles différentes, donc "l'année active" n'est pas globale)
        $enrollment = Enrollment::where('student_id', $studentId)
            ->whereHas('schoolYear', function ($q) use ($student) {
                $q->where('is_active', true)->where('school_id', $student->school_id);
            })
            ->with('schoolClass') // On charge aussi la classe pour l'afficher
            ->first();

        // 3. Récupérer les échéances (tranches) de cette inscription, triées par date
        $installments = [];
        $totalExpected = 0;
        $totalPaid = 0;

        if ($enrollment) {
            $installments = StudentInstallment::where('enrollment_id', $enrollment->id)
                ->orderBy('due_date', 'asc')
                ->get();

            $totalExpected = $installments->sum('amount');
            $totalPaid = $installments->sum('paid_amount');
        }

        $remaining = max(0, $totalExpected - $totalPaid);

        // 4. Récupérer l'historique des reçus pour les téléchargements
        $payments = collect(); // Par défaut, une collection vide

        if ($enrollment) {
            // On utilise enrollment_id au lieu de student_id
            $payments = Payment::where('enrollment_id', $enrollment->id)
                ->orderBy('payment_date', 'desc')
                ->get();
        }

        return view('parent.payments.index', compact(
            'student',
            'siblings',         // ✅ AJOUTÉ ICI POUR CORRIGER L'ERREUR
            'enrollment',
            'installments',
            'payments',
            'totalPaid',
            'totalExpected',
            'remaining'
        ));
    }

    /**
     * Télécharger un reçu de paiement
     */
    public function downloadReceipt($studentId, $paymentId)
    {
        $parent = auth()->user();

        // 1. Vérifier l'accès : l'enfant doit appartenir à ce parent
        $student = $parent->children()
            ->where('students.id', $studentId)
            ->firstOrFail();

        // 2. Récupérer le paiement et vérifier qu'il appartient bien à cet élève via son inscription
        $payment = Payment::where('id', $paymentId)
            ->whereHas('enrollment', function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            })
            ->with(['enrollment.schoolYear', 'enrollment.schoolClass', 'school'])
            ->firstOrFail();

        // 3. Échéances encore en attente, pour l'informatif "reste à payer" du reçu
        $pendingInstallments = StudentInstallment::where('enrollment_id', $payment->enrollment_id)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('due_date', 'asc')
            ->get();

        $schoolClass = $payment->enrollment->schoolClass;
        $schoolYear = $payment->enrollment->schoolYear;
        $school = $payment->school;

        // 4. Générer le même PDF que côté admin (App\PaymentController::receipt)
        $pdf = Pdf::loadView('pdf.receipt', compact(
            'payment', 'student', 'schoolClass', 'schoolYear', 'school', 'pendingInstallments'
        ));

        $filename = 'Recu_Paiement_'.str_pad($payment->id, 6, '0', STR_PAD_LEFT).'.pdf';

        return $pdf->download($filename);
    }
}
