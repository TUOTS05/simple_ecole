<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ReportCard;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Fin d'année : décisions du conseil (admis, redouble, saut de classe) et passage des élèves dans
 * l'année suivante.
 */
class EndOfYearController extends Controller
{
    // 1. Liste des classes pour l'année en cours
    public function index()
    {
        $currentYear = $this->activeYear();

        $classes = SchoolClass::where('school_id', auth()->user()->school_id)
            ->withCount(['students' => function ($q) use ($currentYear) {
                $q->where(function ($query) use ($currentYear) {
                    $query->where('student_school_class.school_year_id', $currentYear->id)
                        ->orWhereNull('student_school_class.school_year_id');
                });
            }])
            ->get();

        return view('app.end-of-year.index', compact('classes', 'currentYear'));
    }

    // 2. Détail d'une classe avec suggestions
    public function show(SchoolClass $class)
    {
        $this->ensureBelongsToSchool($class->school_id);
        $currentYear = $this->activeYear();
        $nextClass = $class->getNextClassForSchoolYear($currentYear->id);

        $students = $class->students()
            ->where(function ($q) use ($currentYear) {
                $q->where('student_school_class.school_year_id', $currentYear->id)
                    ->orWhereNull('student_school_class.school_year_id');
            })
            ->with(['reportCards' => function ($q) use ($currentYear) {
                $q->where('school_year_id', $currentYear->id)
                    ->where(function ($query) {
                        // Recherche insensible à la casse (Trimestriel ou trimestriel)
                        $query->where('period', 'Trimestriel')
                            ->orWhere('period', 'trimestriel');
                    })
                    ->where('quarter', 3);
            }])
            ->orderBy('last_name')
            ->get();

        return view('app.end-of-year.show', compact('class', 'students', 'nextClass', 'currentYear'));
    }

    // 3. Sauvegarder la décision pour un élève
    public function updateDecision(Request $request, Student $student)
    {
        $this->ensureBelongsToSchool($student->school_id);

        $request->validate([
            'decision' => 'required|in:admis,redouble,saut_classe',
            'next_school_class_id' => ['nullable', Rule::exists('school_classes', 'id')->where('school_id', auth()->user()->school_id)],
            'comment' => 'nullable|string|max:500',
        ]);

        $currentYear = $this->activeYear();

        $currentClassId = DB::table('student_school_class')
            ->where('student_id', $student->id)
            ->where(function ($q) use ($currentYear) {
                $q->where('school_year_id', $currentYear->id)
                    ->orWhereNull('school_year_id');
            })
            ->value('school_class_id');

        // On cherche d'abord si le bulletin du 3e trimestre existe déjà
        $existingReportCard = ReportCard::where('student_id', $student->id)
            ->where('school_year_id', $currentYear->id)
            ->where(function ($q) {
                $q->where('period', 'Trimestriel')->orWhere('period', 'trimestriel');
            })
            ->where('quarter', 3)
            ->first();

        if ($existingReportCard) {
            // Mise à jour propre sans toucher à la moyenne
            $existingReportCard->update([
                'end_of_year_decision' => $request->decision,
                'next_school_class_id' => $request->decision === 'redouble' ? $currentClassId : ($request->next_school_class_id ?? null),
                'director_comment' => $request->comment,
                'director_signed' => true,
            ]);
        } else {
            // Création de secours (ex: décision prise avant la saisie des notes)
            // On met average = 0.00 pour satisfaire la contrainte NOT NULL de PostgreSQL
            ReportCard::create([
                'student_id' => $student->id,
                'school_year_id' => $currentYear->id,
                'school_id' => auth()->user()->school_id,
                'school_class_id' => $currentClassId,
                'period' => 'Trimestriel',
                'quarter' => 3,
                'average' => 0.00, // Contourne l'erreur NOT NULL
                'rank' => null,
                'total_students' => null,
                'end_of_year_decision' => $request->decision,
                'next_school_class_id' => $request->decision === 'redouble' ? $currentClassId : ($request->next_school_class_id ?? null),
                'director_comment' => $request->comment,
                'director_signed' => true,
            ]);
        }

        $userName = $student->first_name.' '.$student->last_name;

        return redirect()->back()->with('success', 'Décision enregistrée pour '.$userName);
    }

    // 4. Migration en masse vers l'année supérieure
    public function migrateClass(SchoolClass $class)
    {
        $this->ensureBelongsToSchool($class->school_id);
        $currentYear = $this->activeYear();

        $parts = explode('-', $currentYear->name);
        if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
            $nextYearName = ((int) $parts[0] + 1).'-'.((int) $parts[1] + 1);
        } else {
            $nextYearName = $currentYear->name.' (Suite)';
        }

        $nextYear = SchoolYear::firstOrCreate(
            [
                'name' => $nextYearName,
                'school_id' => $class->school_id,
            ],
            [
                'start_date' => now()->addYear()->startOfYear(),
                'end_date' => now()->addYear()->endOfYear(),
                'is_active' => false,
            ]
        );

        $nextClass = $class->getNextClassForSchoolYear($currentYear->id);

        if (! $nextClass) {
            return redirect()->back()->with('error', 'Aucune classe supérieure trouvée. Veuillez d\'abord créer la classe pour l\'année '.$nextYearName.'.');
        }

        // Toutes les décisions prises pour cette classe, y compris "redouble" : un redoublant doit
        // rester scolarisé (dans la même classe) pour la nouvelle année, pas disparaître du système.
        $reportCards = ReportCard::where('school_year_id', $currentYear->id)
            ->where('school_class_id', $class->id)
            ->where('quarter', 3)
            ->where(function ($q) {
                $q->where('period', 'Trimestriel')->orWhere('period', 'trimestriel');
            })
            ->whereIn('end_of_year_decision', ['admis', 'saut_classe', 'redouble'])
            ->with('student')
            ->get();

        if ($reportCards->isEmpty()) {
            return redirect()->back()->with('error', 'Aucune décision de fin d\'année enregistrée pour cette classe.');
        }

        $migrated = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($reportCards as $reportCard) {
                if (! $reportCard->student) {
                    continue;
                }

                // On respecte la classe de destination choisie par le directeur (utile pour un saut de
                // classe), avec un repli sur la classe supérieure pour "admis" et la classe actuelle
                // pour "redouble".
                $targetClassId = $reportCard->next_school_class_id
                    ?? ($reportCard->end_of_year_decision === 'redouble' ? $class->id : $nextClass->id);

                if (! $targetClassId) {
                    $skipped++;

                    continue;
                }

                $reportCard->student->classes()->attach($targetClassId, [
                    'school_year_id' => $nextYear->id,
                ]);
                $migrated++;
            }
            DB::commit();

            $message = "🎉 {$migrated} élève(s) migré(s) avec succès pour l'année {$nextYearName}.";
            if ($skipped > 0) {
                $message .= " {$skipped} élève(s) ignoré(s) faute de classe de destination.";
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Erreur lors de la migration : '.$e->getMessage());
        }
    }

    /**
     * Année scolaire active de l'école connectée ; 404 si aucune n'est active.
     */
    private function activeYear(): SchoolYear
    {
        $year = SchoolYear::where('school_id', auth()->user()->school_id)->where('is_active', true)->first();
        abort_unless($year, 404, 'Aucune année scolaire active pour votre école.');

        return $year;
    }

    private function ensureBelongsToSchool(?int $schoolId): void
    {
        abort_unless($schoolId !== null && $schoolId === (int) auth()->user()->school_id, 403);
    }
}
