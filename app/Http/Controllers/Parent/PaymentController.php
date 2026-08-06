<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Payment;
use App\Models\Enrollment;
use Illuminate\Http\Request;

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
        
        // 2. Récupérer l'inscription de l'élève pour l'année en cours
        $enrollment = Enrollment::where('student_id', $studentId)
            ->whereHas('schoolYear', function($q) {
                $q->where('is_active', true);
            })
            ->with('schoolClass') // On charge aussi la classe pour l'afficher
            ->first();
        
        // 3. Récupérer les échéances (tranches) de cette inscription, triées par date
        $installments = [];
        $totalExpected = 0;
        $totalPaid = 0;
        
        if ($enrollment) {
            $installments = \App\Models\StudentInstallment::where('enrollment_id', $enrollment->id)
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
            $payments = \App\Models\Payment::where('enrollment_id', $enrollment->id)
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
        $payment = \App\Models\Payment::where('id', $paymentId)
            ->whereHas('enrollment', function($q) use ($studentId) {
                $q->where('student_id', $studentId);
            })
            ->with(['enrollment' => function($q) {
                $q->with(['schoolYear', 'schoolClass', 'student']);
            }])
            ->firstOrFail();
        
        // 3. Afficher la vue du reçu (le PDF sera généré plus tard)
        return view('parent.payments.receipt', compact('student', 'payment'));
    }
}