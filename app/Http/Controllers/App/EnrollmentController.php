<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Mail\ParentWelcomeMail;
use App\Models\Enrollment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnrollmentController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = session('current_school_id');

        $query = Enrollment::where('school_id', $schoolId)
            ->with(['student', 'schoolYear', 'schoolClass', 'studentInstallments']);

        if ($request->filled('school_year_id')) {
            $query->where('school_year_id', $request->school_year_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // ✅ AJOUT DU FILTRE PAR CLASSE
        if ($request->filled('class_id')) {
            $query->where('school_class_id', $request->class_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%');
            });
        }

        $enrollments = $query->orderBy('enrollment_date', 'desc')->paginate(15);
        $enrollments->appends($request->query());

        $schoolYears = SchoolYear::where('school_id', $schoolId)->orderBy('start_date', 'desc')->get();

        // ✅ AJOUT : Récupérer les classes pour le menu déroulant
        $classes = SchoolClass::where('school_id', $schoolId)->orderBy('name')->get();

        return view('app.enrollments.index', compact('enrollments', 'schoolYears', 'classes'));
    }

    public function create()
    {
        $schoolId = session('current_school_id');

        $students = Student::where('school_id', $schoolId)
            ->where('status', 'active')
            ->orderBy('last_name')
            ->get();

        $schoolYears = SchoolYear::where('school_id', $schoolId)
            ->orderBy('start_date', 'desc')
            ->get();

        $classes = SchoolClass::where('school_id', $schoolId)
            ->orderBy('name')
            ->get();

        // Récupérer les infos parent de la session si elles existent
        $parentDetails = session('parent_details', []);

        // Un élève doit toujours être rattaché à une année scolaire active (voir store()).
        if (! $schoolYears->contains('is_active', true)) {
            return redirect()->route('app.school-years.index')
                ->with('error', "Aucune année scolaire active n'est configurée pour votre établissement. Créez-en une (ou activez-en une) avant d'inscrire un élève.");
        }

        return view('app.enrollments.create', compact(
            'students',
            'schoolYears',
            'classes',
            'parentDetails'

        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'gender' => 'required|in:M,F',
            'birth_date' => 'required|date|before_or_equal:today',
            'class_id' => 'required|exists:school_classes,id',
            'section' => 'nullable|string|max:10',
            'status' => 'required|in:active,inactive,suspended',
            'large_family' => 'nullable|boolean',
            'staff_child' => 'nullable|boolean',
            'religion' => 'nullable|string|max:50',
            'admission_date' => 'required|date',
            'receipt_number' => 'required|string|max:50',
            'student_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',

            'father_name' => 'nullable|string|max:100',
            'father_phone' => 'nullable|string|max:20',
            'father_occupation' => 'nullable|string|max:100',
            'mother_name' => 'nullable|string|max:100',
            'mother_phone' => 'nullable|string|max:20',
            'mother_occupation' => 'nullable|string|max:100',

            'guardian_type' => 'required|in:father,mother,other',
            'guardian_first_name' => 'required|string|max:100', // NOUVEAU
            'guardian_last_name' => 'required|string|max:100',  // NOUVEAU
            'guardian_phone' => 'required|string|max:20',
            'guardian_email' => 'required|email|max:100',       // RENDU OBLIGATOIRE
            'guardian_relation' => 'nullable|string|max:50',
            'guardian_occupation' => 'nullable|string|max:100',
            'guardian_address' => 'nullable|string',

            'current_address' => 'nullable|string',
            'permanent_address' => 'nullable|string',
            'previous_school' => 'nullable|string',
            'remarks' => 'nullable|string',
            'action' => 'nullable|string|in:add_sibling',

            'documents.1' => 'nullable|file|extensions:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'documents.2' => 'nullable|file|extensions:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'documents.3' => 'nullable|file|extensions:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'documents.4' => 'nullable|file|extensions:pdf,doc,docx,jpg,jpeg,png|max:2048',
        ]);

        $schoolId = session('current_school_id');
        $year = date('Y');

        // Un élève doit toujours être rattaché à une année scolaire active : sans ce garde-fou,
        // l'élève était créé quand même mais sans inscription (Enrollment) ni échéancier de
        // frais, car le bloc plus bas ne faisait rien silencieusement si aucune année active
        // n'existait.
        $activeYear = SchoolYear::where('school_id', $schoolId)->where('is_active', true)->first();
        if (! $activeYear) {
            return back()->withErrors([
                'class_id' => "Aucune année scolaire active n'est configurée pour votre établissement. Créez-en une avant d'inscrire un élève.",
            ])->withInput();
        }

        // L'email des users est unique globalement (toutes écoles confondues) : si le tuteur
        // saisi correspond à un compte d'une autre école (ou d'un autre rôle), la création
        // plantait plus loin avec une erreur SQL brute. On le détecte ici proprement.
        if (! empty($validated['guardian_email'])) {
            $existingParent = User::where('email', $validated['guardian_email'])->first();
            if ($existingParent && ($existingParent->school_id !== $schoolId || $existingParent->role !== 'parent')) {
                return back()->withErrors([
                    'guardian_email' => "Cette adresse email est déjà utilisée par un autre compte et ne peut pas servir d'email pour le tuteur.",
                ])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            // Gestion des documents avant la création de l'élève
            $documentsData = [];
            for ($i = 1; $i <= 4; $i++) {
                if ($request->hasFile("documents.$i") && $request->file("documents.$i")->isValid()) {
                    $path = $request->file("documents.$i")->store('students/documents', 'public');
                    $documentsData["doc_$i"] = $path;
                }
            }

            // 1. Générer le Numéro d'Admission
            $lastStudent = Student::where('school_id', $schoolId)->whereYear('created_at', $year)->orderBy('id', 'desc')->first();
            $nextAdmissionNum = $lastStudent && $lastStudent->admission_number ? (intval(substr($lastStudent->admission_number, -4)) + 1) : 1;
            $admissionNumber = 'ADM-'.$year.'-'.str_pad($nextAdmissionNum, 4, '0', STR_PAD_LEFT);

            // 2. Gestion de la photo
            $photoPath = null;
            if ($request->hasFile('student_photo')) {
                $photoPath = $request->file('student_photo')->store('students/photos', 'public');
            }

            // 3. Création de l'élève
            $student = Student::create([
                'school_id' => $schoolId,
                'admission_number' => $admissionNumber,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'gender' => $validated['gender'],
                'birth_date' => $validated['birth_date'],
                'status' => $validated['status'],
                'section' => $validated['section'] ?? null,
                'large_family' => $validated['large_family'] ?? false,
                'staff_child' => $validated['staff_child'] ?? false,
                'religion' => $validated['religion'] ?? null,
                'admission_date' => $validated['admission_date'],
                'receipt_number' => $validated['receipt_number'],
                'photo' => $photoPath,

                'father_name' => $validated['father_name'] ?? null,
                'father_phone' => $validated['father_phone'] ?? null,
                'father_occupation' => $validated['father_occupation'] ?? null,
                'mother_name' => $validated['mother_name'] ?? null,
                'mother_phone' => $validated['mother_phone'] ?? null,
                'mother_occupation' => $validated['mother_occupation'] ?? null,

                'guardian_type' => $validated['guardian_type'],
                // On concatène pour l'ancien champ, tout en ayant les données séparées pour le User
                'guardian_name' => trim($validated['guardian_first_name'].' '.$validated['guardian_last_name']),
                'guardian_phone' => $validated['guardian_phone'],
                'guardian_relation' => $validated['guardian_relation'] ?? null,
                'guardian_email' => $validated['guardian_email'],
                'guardian_occupation' => $validated['guardian_occupation'] ?? null,
                'guardian_address' => $validated['guardian_address'] ?? null,

                'current_address' => $validated['current_address'] ?? null,
                'permanent_address' => $validated['permanent_address'] ?? null,
                'previous_school' => $validated['previous_school'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'documents' => ! empty($documentsData) ? $documentsData : null,
            ]);

            // ==========================================
            // 4. GESTION INTELLIGENTE DU COMPTE PARENT
            // ==========================================
            $newParentPassword = 'Ecole2024!';
            $isNewParentAccount = false;
            $parentUser = null;
            if (! empty($validated['guardian_email'])) {
                // a) Créer ou récupérer l'utilisateur Parent
                // Note : role et school_id ne sont pas mass-assignables (protection contre
                // l'élévation de privilèges) ; le where() de firstOrCreate n'est pas affecté
                // (ce n'est pas du mass assignment), mais on affecte school_id/role
                // explicitement après création.
                $parentUser = User::firstOrCreate(
                    [
                        'email' => $validated['guardian_email'],
                        'school_id' => $schoolId,
                    ],
                    [
                        'first_name' => $validated['guardian_first_name'],
                        'last_name' => $validated['guardian_last_name'],
                        'password' => bcrypt($newParentPassword), // Mot de passe par défaut
                        'phone' => $validated['guardian_phone'],
                    ]
                );
                $isNewParentAccount = $parentUser->wasRecentlyCreated;

                if ($isNewParentAccount) {
                    $parentUser->role = 'parent';
                    $parentUser->school_id = $schoolId;
                    $parentUser->save();
                }

                // b) Lier ce parent à l'élève dans la table pivot (avec school_id)
                DB::table('parent_student')->updateOrInsert(
                    [
                        'parent_id' => $parentUser->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'school_id' => $schoolId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
            // ==========================================

            // 5. Créer l'inscription (Enrollment) — l'année active a déjà été vérifiée plus haut
            $enrollment = Enrollment::create([
                'school_id' => $schoolId,
                'student_id' => $student->id,
                'school_year_id' => $activeYear->id,
                'school_class_id' => $validated['class_id'],
                'enrollment_date' => $validated['admission_date'],
                'status' => 'enrolled',
            ]);

            $student->classes()->attach($validated['class_id']);

            // 6. GÉNÉRATION AUTOMATIQUE DES ÉCHÉANCES
            $this->generateFeeSchedule($enrollment, $validated['class_id'], $validated['admission_date']);

            DB::commit();

            if ($isNewParentAccount && $parentUser) {
                try {
                    $school = session('current_school') ?? School::find($schoolId);
                    Mail::to($parentUser->email)->send(
                        new ParentWelcomeMail(
                            trim($parentUser->first_name.' '.$parentUser->last_name),
                            trim($student->first_name.' '.$student->last_name),
                            $school->name ?? 'votre école',
                            $parentUser->email,
                            $newParentPassword
                        )
                    );
                } catch (\Throwable $e) {
                    Log::error('Échec envoi email de bienvenue au parent : '.$e->getMessage());
                }
            }

            // 7. Gestion du bouton "Ajouter enfant de mêmes parents"
            if ($request->input('action') === 'add_sibling') {
                session()->put('parent_details', [
                    'father_name' => $validated['father_name'],
                    'father_phone' => $validated['father_phone'],
                    'father_occupation' => $validated['father_occupation'],
                    'mother_name' => $validated['mother_name'],
                    'mother_phone' => $validated['mother_phone'],
                    'mother_occupation' => $validated['mother_occupation'],
                    'guardian_first_name' => $validated['guardian_first_name'], // Mis à jour
                    'guardian_last_name' => $validated['guardian_last_name'],   // Mis à jour
                    'guardian_phone' => $validated['guardian_phone'],
                    'guardian_relation' => $validated['guardian_relation'],
                    'guardian_email' => $validated['guardian_email'],
                    'guardian_occupation' => $validated['guardian_occupation'],
                    'guardian_address' => $validated['guardian_address'],
                    'current_address' => $validated['current_address'],
                    'permanent_address' => $validated['permanent_address'],
                ]);

                return redirect()->route('app.students.create') // Redirige vers la création d'élève, pas enrollment
                    ->with('success', "✅ {$student->first_name} enregistré(e) ! Compte parent lié. Vous pouvez ajouter un frère/une sœur.")
                    ->withInput();
            }

            return redirect()->route('app.students.index')
                ->with('success', "✅ Inscription réussie ! Matricule : {$student->matricule}. Un compte parent a été créé/lié.");

        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Erreur : '.$e->getMessage()])->withInput();
        }
    }

    public function show(Enrollment $enrollment)
    {
        if ($enrollment->school_id !== session('current_school_id')) {
            abort(403);
        }

        $enrollment->load(['student', 'schoolYear', 'schoolClass', 'payments']);

        return view('app.enrollments.show', compact('enrollment'));
    }

    public function edit(Enrollment $enrollment)
    {
        if ($enrollment->school_id !== session('current_school_id')) {
            abort(403);
        }

        $schoolId = session('current_school_id');
        $students = Student::where('school_id', $schoolId)
            ->where('status', 'active')
            ->orderBy('last_name')
            ->get();
        $schoolYears = SchoolYear::where('school_id', $schoolId)->orderBy('start_date', 'desc')->get();
        $schoolClasses = SchoolClass::where('school_id', $schoolId)->orderBy('name')->get();

        return view('app.enrollments.edit', compact('enrollment', 'students', 'schoolYears', 'schoolClasses'));
    }

    public function update(Request $request, Enrollment $enrollment)
    {
        if ($enrollment->school_id !== session('current_school_id')) {
            abort(403);
        }

        $validated = $request->validate([
            'school_class_id' => 'nullable|exists:school_classes,id',
            'status' => 'required|in:reserved,enrolled,withdrawn',
            'notes' => 'nullable|string',
        ]);

        $enrollment->update($validated);

        return redirect()->route('app.enrollments.index')
            ->with('success', 'Inscription mise à jour !');
    }

    public function destroy(Enrollment $enrollment)
    {
        if ($enrollment->school_id !== session('current_school_id')) {
            abort(403);
        }

        if ($enrollment->payments()->count() > 0) {
            return redirect()->route('app.enrollments.index')
                ->with('error', 'Impossible de supprimer cette inscription car elle contient des paiements.');
        }

        $enrollment->delete();

        return redirect()->route('app.enrollments.index')
            ->with('success', 'Inscription supprimée !');
    }

    /**
     * Exporter la liste des inscriptions en format CSV (Compatible Excel)
     */
    public function export(Request $request)
    {
        $schoolId = session('current_school_id');

        $query = Enrollment::where('school_id', $schoolId)
            ->with(['student', 'schoolYear', 'schoolClass', 'studentInstallments']);

        if ($request->filled('school_year_id')) {
            $query->where('school_year_id', $request->school_year_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // ✅ FILTRE PAR CLASSE
        if ($request->filled('class_id')) {
            $query->where('school_class_id', $request->class_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%');
            });
        }

        $enrollments = $query->orderBy('enrollment_date', 'desc')->get();

        $filename = 'export_inscriptions_'.date('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($enrollments) {
            $file = fopen('php://output', 'w');

            // BOM UTF-8 pour les accents dans Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'Date d\'inscription',
                'Nom de l\'élève',
                'Matricule',
                'Année Scolaire',
                'Classe',
                'Statut',
                'Frais d\'inscription',
                'Reste à payer (Scolarité)',
            ]);

            foreach ($enrollments as $enrollment) {
                $studentName = trim(($enrollment->student->last_name ?? '').' '.($enrollment->student->first_name ?? ''));
                $matricule = $enrollment->student->matricule ?? 'N/A';
                $yearName = $enrollment->schoolYear->name ?? 'N/A';
                $className = $enrollment->schoolClass->name ?? 'N/A';

                $statusMap = [
                    'enrolled' => 'Inscrit',
                    'reserved' => 'Réservé',
                    'withdrawn' => 'Retiré',
                ];
                $status = $statusMap[$enrollment->status] ?? $enrollment->status;

                $regFeeStatus = $enrollment->registration_fee_paid ? 'Payé' : 'Non payé';

                $totalTuition = $enrollment->tuition_fee_total ?? 0;
                $paidTuition = $enrollment->tuition_fee_paid ?? 0;
                $remainingTuition = max(0, $totalTuition - $paidTuition);

                fputcsv($file, [
                    Carbon::parse($enrollment->enrollment_date)->format('d/m/Y'),
                    $studentName,
                    $matricule,
                    $yearName,
                    $className,
                    $status,
                    $regFeeStatus,
                    $remainingTuition > 0 ? number_format($remainingTuition, 0, ',', ' ').' FCFA' : 'Soldé',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
