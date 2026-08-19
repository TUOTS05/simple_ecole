<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\StudentInstallment;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StudentsExport;
use Barryvdh\DomPDF\Facade\Pdf;


class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $schoolId = session('current_school_id');

        $schoolYears = SchoolYear::where('school_id', $schoolId)->orderBy('start_date', 'desc')->get();
        $classes = SchoolClass::where('school_id', $schoolId)->orderBy('name')->get();

        $query = Student::where('school_id', $schoolId);

        if ($request->filled('school_year_id')) {
            $query->whereHas('enrollments', function ($q) use ($request) {
                $q->where('school_year_id', $request->school_year_id);
            });
        }

        if ($request->filled('class_id')) {
            $query->whereHas('classes', function ($q) use ($request) {
                $q->where('school_classes.id', $request->class_id);
            });
        }

        if ($request->filled('section')) {
            $query->where('section', $request->section);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('matricule', 'like', '%' . $search . '%');
            });
        }

        $students = $query->with(['classes'])
            ->orderBy('last_name', 'asc')
            ->orderBy('first_name', 'asc')
            ->paginate(15);

        $students->appends($request->query());

        return view('app.students.index', compact('students', 'schoolYears', 'classes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $schoolId = session('current_school_id');

        $classes = SchoolClass::where('school_id', $schoolId)->orderBy('name')->get();
        $schoolYears = SchoolYear::where('school_id', $schoolId)->orderBy('start_date', 'desc')->get();
        $parentDetails = session('parent_details', []);

        return view('app.enrollments.create', compact('classes', 'schoolYears', 'parentDetails'));
    }



    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'gender' => 'required|in:M,F',
            'birth_date' => 'required|date|before_or_equal:today',
            'class_id' => 'required|exists:school_classes,id',
            'section' => 'required|string|max:10',
            'status' => 'required|in:active,inactive,suspended',
            'large_family' => 'required|boolean',
            'staff_child' => 'required|boolean',
            'religion' => 'nullable|string|max:50',
            'admission_date' => 'required|date',
            'receipt_number' => 'nullable|string|max:50',
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
            'same_guardian_address' => 'nullable|boolean',
            'same_permanent_address' => 'nullable|boolean',
            'previous_school' => 'nullable|string',
            'remarks' => 'nullable|string',
            'action' => 'nullable|string|in:add_sibling',

            'documents.1' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:2048',
            'documents.2' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:2048',
            'documents.3' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:2048',
            'documents.4' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:2048',
        ]);

        $schoolId = session('current_school_id');
        $year = date('Y');

        // Plafond d'élèves de l'abonnement : jusqu'ici School.max_students n'était jamais vérifié
        // nulle part, rendant le plafonnement du plan purement décoratif.
        $school = \App\Models\School::find($schoolId);
        if ($school && $school->max_students) {
            $activeStudentCount = Student::where('school_id', $schoolId)->where('status', 'active')->count();
            if ($activeStudentCount >= $school->max_students) {
                return back()->withErrors([
                    'class_id' => "Le plafond de {$school->max_students} élèves actifs de votre abonnement est atteint. Contactez le support pour augmenter votre plan.",
                ])->withInput();
            }
        }

        // ✅ AJOUT : Gestion des 4 documents avant la création
        $documentsData = [];
        for ($i = 1; $i <= 4; $i++) {
            if ($request->hasFile("documents.$i")) {
                $path = $request->file("documents.$i")->store('students/documents', 'public');
                $documentsData["doc_$i"] = $path;
            }
        }

        // Si le client n'a pas envoyé de numéro de reçu, on en génère un côté serveur.
        if (empty($validated['receipt_number'])) {
            $validated['receipt_number'] = 'REC-' . $year . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        DB::beginTransaction();
        try {
            // 1. Générer le Numéro d'Admission
            $lastStudent = Student::where('school_id', $schoolId)->whereYear('created_at', $year)->orderBy('id', 'desc')->first();
            $nextAdmissionNum = $lastStudent && $lastStudent->admission_number ? (intval(substr($lastStudent->admission_number, -4)) + 1) : 1;
            $admissionNumber = 'ADM-' . $year . '-' . str_pad($nextAdmissionNum, 4, '0', STR_PAD_LEFT);

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
                'guardian_name' => trim($validated['guardian_first_name'] . ' ' . $validated['guardian_last_name']),
                'guardian_phone' => $validated['guardian_phone'],
                'guardian_relation' => $validated['guardian_relation'] ?? null,
                'guardian_email' => $validated['guardian_email'],
                'guardian_occupation' => $validated['guardian_occupation'] ?? null,
                'guardian_address' => $validated['guardian_address'] ?? null,

                'current_address' => $validated['current_address'] ?? null,
                'permanent_address' => $validated['permanent_address'] ?? null,
                'previous_school' => $validated['previous_school'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'documents' => !empty($documentsData) ? $documentsData : null,
            ]);

            // ==========================================
            // 4. GESTION INTELLIGENTE DU COMPTE PARENT
            // ==========================================
            if (!empty($validated['guardian_email'])) {
                // a) Créer ou récupérer l'utilisateur Parent
                $parentUser = \App\Models\User::firstOrCreate(
                    [
                        'email' => $validated['guardian_email'],
                        'school_id' => $schoolId
                    ],
                    [
                        'first_name' => $validated['guardian_first_name'],
                        'last_name' => $validated['guardian_last_name'],
                        'role' => 'parent',
                        'password' => bcrypt('Ecole2024!'), // Mot de passe par défaut
                        'phone' => $validated['guardian_phone'],
                    ]
                );

                // b) Lier ce parent à l'élève dans la table pivot (avec school_id)
                \Illuminate\Support\Facades\DB::table('parent_student')->updateOrInsert(
                    [
                        'parent_id' => $parentUser->id,
                        'student_id' => $student->id
                    ],
                    [
                        'school_id' => $schoolId,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }
            // ==========================================

            // 5. Créer l'inscription (Enrollment)
            $activeYear = SchoolYear::where('school_id', $schoolId)->where('is_active', true)->first();
            if ($activeYear) {
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
                $this->generateFeeSchedule($enrollment, $validated['class_id']);
            }

            DB::commit();

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
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur : ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Génère automatiquement les échéances de paiement pour un élève inscrit
     */
    private function generateFeeSchedule($enrollment, $classId)
    {
        $schoolClass = \App\Models\SchoolClass::find($classId);
        if (!$schoolClass) return;

        \App\Models\StudentInstallment::generateScheduleFor($enrollment, $schoolClass, $enrollment->enrollment_date);
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student)
    {
        // Vérifier que l'élève appartient à l'école courante
        if ($student->school_id !== session('current_school_id')) {
            abort(403, 'Accès non autorisé à cet élève.');
        }

        // Charger toutes les relations nécessaires pour l'affichage
        $student->load([
            'classes',
            'enrollments.schoolYear',
            'parents'
        ]);

        return view('app.students.show', compact('student'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Student $student)
    {
        // 1. Vérification de sécurité
        if ($student->school_id !== session('current_school_id')) {
            abort(403);
        }

        $schoolId = session('current_school_id');
        $classes = SchoolClass::where('school_id', $schoolId)->orderBy('name')->get();
        $schoolYears = SchoolYear::where('school_id', $schoolId)->orderBy('start_date', 'desc')->get();

        // 2. AJOUT CRUCIAL : On charge explicitement la relation 'classes' 
        // pour que $student->classes->first()?->id fonctionne parfaitement dans la vue
        $student->load('classes');

        // 3. On envoie les variables à la vue
        return view('app.students.edit', compact('student', 'classes', 'schoolYears'));
    }

    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, Student $student)
    // {
    //     if ($student->school_id !== session('current_school_id')) {
    //         abort(403);
    //     }

    //     // Validation simplifiée pour la mise à jour
    //     $validated = $request->validate([
    //         'first_name' => 'required|string|max:100',
    //         'last_name' => 'required|string|max:100',
    //         'status' => 'required|in:active,inactive,suspended',
    //         // Ajoutez les autres champs nécessaires ici si vous avez un formulaire d'édition complet
    //     ]);

    //     $student->update($validated);

    //     return redirect()->route('app.students.index')
    //         ->with('success', 'Informations de l\'élève mises à jour avec succès !');
    // }


    // public function update(Request $request, Student $student)
    // {
    //     if ($student->school_id !== session('current_school_id')) {
    //         abort(403);
    //     }

    //     $validated = $request->validate([
    //         'first_name' => 'required|string|max:100',
    //         'last_name' => 'required|string|max:100',
    //         'gender' => 'required|in:M,F',
    //         'birth_date' => 'required|date|before_or_equal:today',
    //         'class_id' => 'required|exists:school_classes,id',
    //         'section' => 'nullable|string|max:10',
    //         'status' => 'required|in:active,inactive,suspended',
    //         'large_family' => 'required|boolean',
    //         'staff_child' => 'required|boolean',
    //         'religion' => 'nullable|string|max:50',
    //         'admission_date' => 'required|date',
    //         'receipt_number' => 'nullable|string|max:50',
    //         'student_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',

    //         'father_name' => 'nullable|string|max:100',
    //         'father_phone' => 'nullable|string|max:20',
    //         'father_occupation' => 'nullable|string|max:100',
    //         'mother_name' => 'nullable|string|max:100',
    //         'mother_phone' => 'nullable|string|max:20',
    //         'mother_occupation' => 'nullable|string|max:100',

    //         'guardian_type' => 'required|in:father,mother,other',
    //         'guardian_first_name' => 'required|string|max:100',
    //         'guardian_last_name' => 'required|string|max:100',
    //         'guardian_phone' => 'required|string|max:20',
    //         'guardian_email' => 'required|email|max:100',
    //         'guardian_relation' => 'nullable|string|max:50',
    //         'guardian_occupation' => 'nullable|string|max:100',
    //         'guardian_address' => 'nullable|string',

    //         'current_address' => 'nullable|string',
    //         'permanent_address' => 'nullable|string',
    //         'previous_school' => 'nullable|string',
    //         'remarks' => 'nullable|string',

    //         'documents.1' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:2048',
    //         'documents.2' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:2048',
    //         'documents.3' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:2048',
    //         'documents.4' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:2048',
    //     ]);

    //     $schoolId = session('current_school_id');

    //     DB::beginTransaction();
    //     try {
    //         // 1. Gestion de la photo (si nouvelle photo fournie)
    //         $photoPath = $student->photo;
    //         if ($request->hasFile('student_photo')) {
    //             if ($photoPath) {
    //                 \Storage::disk('public')->delete($photoPath);
    //             }
    //             $photoPath = $request->file('student_photo')->store('students/photos', 'public');

    //             // ✅ AJOUT : Gestion intelligente des 4 documents
    //         $documentsData = $student->documents ?? []; // Récupère les anciens ou un tableau vide
            
    //         for ($i = 1; $i <= 4; $i++) {
    //             if ($request->hasFile("documents.$i")) {
    //                 // Supprimer l'ancien fichier s'il existe
    //                 $oldPath = $documentsData["doc_$i"] ?? null;
    //                 if ($oldPath) {
    //                     \Storage::disk('public')->delete($oldPath);
    //                 }
    //                 // Sauvegarder le nouveau
    //                 $documentsData["doc_$i"] = $request->file("documents.$i")->store('students/documents', 'public');
    //             }
    //         }
    //         }

    //         // 2. Mise à jour des données de l'élève
    //         $student->update([
    //             'first_name' => $validated['first_name'],
    //             'last_name' => $validated['last_name'],
    //             'gender' => $validated['gender'],
    //             'birth_date' => $validated['birth_date'],
    //             'status' => $validated['status'],
    //             'section' => $validated['section'],
    //             'large_family' => $validated['large_family'],
    //             'staff_child' => $validated['staff_child'],
    //             'religion' => $validated['religion'],
    //             'admission_date' => $validated['admission_date'],
    //             'receipt_number' => $validated['receipt_number'],
    //             'photo' => $photoPath,

    //             'father_name' => $validated['father_name'],
    //             'father_phone' => $validated['father_phone'],
    //             'father_occupation' => $validated['father_occupation'],
    //             'mother_name' => $validated['mother_name'],
    //             'mother_phone' => $validated['mother_phone'],
    //             'mother_occupation' => $validated['mother_occupation'],

    //             'guardian_type' => $validated['guardian_type'],
    //             'guardian_name' => trim($validated['guardian_first_name'] . ' ' . $validated['guardian_last_name']),
    //             'guardian_phone' => $validated['guardian_phone'],
    //             'guardian_relation' => $validated['guardian_relation'],
    //             'guardian_email' => $validated['guardian_email'],
    //             'guardian_occupation' => $validated['guardian_occupation'],
    //             'guardian_address' => $validated['guardian_address'],

    //             'current_address' => $validated['current_address'],
    //             'permanent_address' => $validated['permanent_address'],
    //             'previous_school' => $validated['previous_school'],
    //             'remarks' => $validated['remarks'],
    //             'documents' => $documentsData,
    //         ]);

    //         // 3. Mise à jour de la classe (via la table pivot)
    //         $student->classes()->sync([$validated['class_id']]);

    //         // Mise à jour de l'inscription active
    //         $activeYear = \App\Models\SchoolYear::where('school_id', $schoolId)->where('is_active', true)->first();
    //         if ($activeYear) {
    //             \App\Models\Enrollment::updateOrCreate(
    //                 [
    //                     'school_id' => $schoolId,
    //                     'student_id' => $student->id,
    //                     'school_year_id' => $activeYear->id,
    //                 ],
    //                 [
    //                     'school_class_id' => $validated['class_id'],
    //                     'status' => 'enrolled',
    //                 ]
    //             );
    //         }

    //         // 4. GESTION INTELLIGENTE DU COMPTE PARENT (Mise à jour ou Création)
    //         if (!empty($validated['guardian_email'])) {
    //             $parentUser = \App\Models\User::updateOrCreate(
    //                 [
    //                     'email' => $validated['guardian_email'],
    //                     'school_id' => $schoolId
    //                 ],
    //                 [
    //                     'first_name' => $validated['guardian_first_name'],
    //                     'last_name' => $validated['guardian_last_name'],
    //                     'role' => 'parent',
    //                     'phone' => $validated['guardian_phone'],
    //                     // Le mot de passe n'est pas touché s'il existe déjà
    //                 ]
    //             );

    //             // Lier ce parent à l'élève dans la table pivot (de manière élégante avec Eloquent)
    //             $student->parents()->syncWithoutDetaching([
    //                 $parentUser->id => ['school_id' => $schoolId]
    //             ]);
    //         }

    //         DB::commit();

    //         return redirect()->route('app.students.index')
    //             ->with('success', "✅ Informations de {$student->first_name} mises à jour avec succès ! Le compte parent a été synchronisé.");
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return back()->withErrors(['error' => 'Erreur lors de la mise à jour : ' . $e->getMessage()])->withInput();
    //     }
    // }


        public function update(Request $request, Student $student)
    {
        if ($student->school_id !== session('current_school_id')) {
            abort(403);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'gender' => 'required|in:M,F',
            'birth_date' => 'required|date|before_or_equal:today',
            'class_id' => 'required|exists:school_classes,id',
            'section' => 'nullable|string|max:10',
            'status' => 'required|in:active,inactive,suspended',
            'large_family' => 'required|boolean',
            'staff_child' => 'required|boolean',
            'religion' => 'nullable|string|max:50',
            'admission_date' => 'required|date',
            'receipt_number' => 'nullable|string|max:50',
            'student_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',

            'father_name' => 'nullable|string|max:100',
            'father_phone' => 'nullable|string|max:20',
            'father_occupation' => 'nullable|string|max:100',
            'mother_name' => 'nullable|string|max:100',
            'mother_phone' => 'nullable|string|max:20',
            'mother_occupation' => 'nullable|string|max:100',

            'guardian_type' => 'required|in:father,mother,other',
            'guardian_first_name' => 'required|string|max:100',
            'guardian_last_name' => 'required|string|max:100',
            'guardian_phone' => 'required|string|max:20',
            'guardian_email' => 'required|email|max:100',
            'guardian_relation' => 'nullable|string|max:50',
            'guardian_occupation' => 'nullable|string|max:100',
            'guardian_address' => 'nullable|string',

            'current_address' => 'nullable|string',
            'permanent_address' => 'nullable|string',
            'previous_school' => 'nullable|string',
            'remarks' => 'nullable|string',

            'documents.1' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:2048',
            'documents.2' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:2048',
            'documents.3' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:2048',
            'documents.4' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:2048',
        ]);

        $schoolId = session('current_school_id');

        DB::beginTransaction();
        try {
            // 1. Gestion de la photo (si nouvelle photo fournie)
            $photoPath = $student->photo;
            if ($request->hasFile('student_photo')) {
                if ($photoPath) {
                    \Storage::disk('public')->delete($photoPath);
                }
                $photoPath = $request->file('student_photo')->store('students/photos', 'public');
            }

            // 2. ✅ Gestion intelligente des 4 documents (HORS du bloc photo, bien indenté)
            $documentsData = $student->documents ?? []; 
            
            for ($i = 1; $i <= 4; $i++) {
                if ($request->hasFile("documents.$i")) {
                    // Supprimer l'ancien fichier s'il existe
                    $oldPath = $documentsData["doc_$i"] ?? null;
                    if ($oldPath) {
                        \Storage::disk('public')->delete($oldPath);
                    }
                    // Sauvegarder le nouveau
                    $documentsData["doc_$i"] = $request->file("documents.$i")->store('students/documents', 'public');
                }
            }

            // 3. Mise à jour des données de l'élève
            $student->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'gender' => $validated['gender'],
                'birth_date' => $validated['birth_date'],
                'status' => $validated['status'],
                'section' => $validated['section'],
                'large_family' => $validated['large_family'],
                'staff_child' => $validated['staff_child'],
                'religion' => $validated['religion'],
                'admission_date' => $validated['admission_date'],
                'receipt_number' => $validated['receipt_number'],
                'photo' => $photoPath,

                'father_name' => $validated['father_name'],
                'father_phone' => $validated['father_phone'],
                'father_occupation' => $validated['father_occupation'],
                'mother_name' => $validated['mother_name'],
                'mother_phone' => $validated['mother_phone'],
                'mother_occupation' => $validated['mother_occupation'],

                'guardian_type' => $validated['guardian_type'],
                'guardian_name' => trim($validated['guardian_first_name'] . ' ' . $validated['guardian_last_name']),
                'guardian_phone' => $validated['guardian_phone'],
                'guardian_relation' => $validated['guardian_relation'],
                'guardian_email' => $validated['guardian_email'],
                'guardian_occupation' => $validated['guardian_occupation'],
                'guardian_address' => $validated['guardian_address'],

                'current_address' => $validated['current_address'],
                'permanent_address' => $validated['permanent_address'],
                'previous_school' => $validated['previous_school'],
                'remarks' => $validated['remarks'],
                'documents' => $documentsData, // ✅ Sauvegarde du JSON
            ]);

            // 4. Mise à jour de la classe (via la table pivot)
            $student->classes()->sync([$validated['class_id']]);

            // Mise à jour de l'inscription active
            $activeYear = \App\Models\SchoolYear::where('school_id', $schoolId)->where('is_active', true)->first();
            if ($activeYear) {
                \App\Models\Enrollment::updateOrCreate(
                    [
                        'school_id' => $schoolId,
                        'student_id' => $student->id,
                        'school_year_id' => $activeYear->id,
                    ],
                    [
                        'school_class_id' => $validated['class_id'],
                        'status' => 'enrolled',
                    ]
                );
            }

            // 5. GESTION INTELLIGENTE DU COMPTE PARENT (Mise à jour ou Création)
            if (!empty($validated['guardian_email'])) {
                $parentUser = \App\Models\User::updateOrCreate(
                    [
                        'email' => $validated['guardian_email'],
                        'school_id' => $schoolId
                    ],
                    [
                        'first_name' => $validated['guardian_first_name'],
                        'last_name' => $validated['guardian_last_name'],
                        'role' => 'parent',
                        'phone' => $validated['guardian_phone'],
                    ]
                );

                $student->parents()->syncWithoutDetaching([
                    $parentUser->id => ['school_id' => $schoolId]
                ]);
            }

            DB::commit();

            return redirect()->route('app.students.index')
                ->with('success', "✅ Informations de {$student->first_name} mises à jour avec succès ! Le compte parent a été synchronisé.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur lors de la mise à jour : ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        if ($student->school_id !== session('current_school_id')) {
            abort(403);
        }

        // Vérifier s'il y a des inscriptions ou paiements avant de supprimer (optionnel mais recommandé)
        if ($student->enrollments()->count() > 0) {
            return redirect()->route('app.students.index')
                ->with('error', 'Impossible de supprimer cet élève car il a des inscriptions ou des paiements associés.');
        }

        $student->delete();

        return redirect()->route('app.students.index')
            ->with('success', 'Élève supprimé avec succès !');
    }

    public function exportExcel(\Illuminate\Http\Request $request)
    {
        $schoolId = session('current_school_id');
        $classId = $request->get('class_id'); // Récupère la classe filtrée

        $filename = $classId ? 'eleves_classe_' . $classId . '_' . date('Y-m-d') : 'tous_les_eleves_' . date('Y-m-d');

        return Excel::download(new StudentsExport($schoolId, $classId), $filename . '.xlsx');
    }



    public function exportPdf(\Illuminate\Http\Request $request)
    {
        $schoolId = session('current_school_id');
        $classId = $request->get('class_id');
        $user = auth()->user();

        // 1. Récupération des élèves
        $query = \App\Models\Student::where('school_id', $schoolId);

        $className = 'Toutes les classes';
        if ($classId) {
            $query->whereHas('enrollments', function ($q) use ($classId) {
                $q->where('school_class_id', $classId);
            });
            $class = \App\Models\SchoolClass::find($classId);
            $className = $class ? $class->name : 'Classe inconnue';
        }

        $students = $query->orderBy('last_name')->orderBy('first_name')->get();

        // 2. Calcul des statistiques (Masculin / Féminin)
        $totalStudents = $students->count();
        $maleCount = $students->where('gender', 'M')->count();
        $femaleCount = $students->where('gender', 'F')->count();

        $maleRate = $totalStudents > 0 ? round(($maleCount / $totalStudents) * 100) : 0;
        $femaleRate = $totalStudents > 0 ? round(($femaleCount / $totalStudents) * 100) : 0;

        // 3. Nom de l'utilisateur connecté
        $userName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'Non spécifié');

        // 4. Année scolaire et Logo
        $schoolYear = '2025-2026'; // Adaptez si vous avez une relation dynamique

        $schoolLogoPath = null;
        // Adaptez 'school->logo' selon votre modèle réel (ex: $user->school->logo)
        if (isset($user->school) && $user->school->logo) {
            $schoolLogoPath = public_path('storage/' . $user->school->logo);
        }
        if (!$schoolLogoPath || !file_exists($schoolLogoPath)) {
            $schoolLogoPath = public_path('images/default-logo.png'); // Fallback
        }

        // 5. Génération du PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('app.exports.students_pdf', compact(
            'students',
            'className',
            'totalStudents',
            'maleCount',
            'femaleCount',
            'maleRate',
            'femaleRate',
            'userName',
            'schoolYear',
            'schoolLogoPath'
        ));

        $pdf->setPaper('a4', 'portrait');

        $filename = $classId ? 'liste_classe_' . $classId . '_' . date('Y-m-d') : 'tous_les_eleves_' . date('Y-m-d');
        return $pdf->download($filename . '.pdf');
    }
}
