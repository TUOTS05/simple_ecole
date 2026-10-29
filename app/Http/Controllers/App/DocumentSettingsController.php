<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Payment;
use App\Models\ReportCard;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\StudentInstallment;
use App\Models\Subject;
use App\Support\DocumentSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Paramétrage par l'école de ses documents PDF (en-tête officiel, couleurs, cachet et
 * signature, barème des appréciations, champs affichés). Voir App\Support\DocumentSettings.
 */
class DocumentSettingsController extends Controller
{
    /**
     * Page de personnalisation des documents PDF de l'école.
     */
    public function edit()
    {
        $school = School::findOrFail(session('current_school_id'));

        return view('app.settings.documents', [
            'school' => $school,
            'settings' => DocumentSettings::for($school),
        ]);
    }

    /**
     * Enregistre les réglages des documents (en-tête, couleurs, bulletin, carte, reçu) et les images
     * de cachet et de signature.
     */
    public function update(Request $request)
    {
        $school = School::findOrFail(session('current_school_id'));
        $color = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];

        $validated = $request->validate([
            'header.lines' => 'nullable|string|max:500',
            'header.motto' => 'nullable|string|max:150',
            'header.city' => 'nullable|string|max:100',

            'branding.primary_color' => $color,
            'branding.accent_color' => $color,
            'branding.signatory_title' => 'nullable|string|max:100',
            'branding.signatory_name' => 'nullable|string|max:100',
            'stamp' => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
            'signature' => 'nullable|image|mimes:png,jpg,jpeg|max:1024',

            'report_card.title_monthly' => 'required|string|max:80',
            'report_card.title_quarterly' => 'required|string|max:80',
            'report_card.teacher_box_title' => 'required|string|max:100',
            'report_card.footer_note' => 'nullable|string|max:300',
            'report_card.scale' => 'required|array|min:1|max:10',
            'report_card.scale.*.min' => 'nullable|numeric|min:0|max:20',
            'report_card.scale.*.label' => 'nullable|string|max:40',

            'student_card.title' => 'required|string|max:60',
            'student_card.footer_note' => 'nullable|string|max:120',

            'receipt.title' => 'required|string|max:60',
            'receipt.currency' => 'required|string|max:10',
            'receipt.signature_label' => 'required|string|max:60',
            'receipt.footer_text' => 'nullable|string|max:150',
        ]);

        $current = DocumentSettings::for($school);

        // Lignes vides du barème ignorées (le formulaire propose des lignes libres en plus)
        $scale = collect($validated['report_card']['scale'])
            ->filter(fn ($step) => isset($step['min']) && filled($step['label'] ?? null))
            ->map(fn ($step) => ['min' => (float) $step['min'], 'label' => trim($step['label'])])
            ->sortByDesc('min')
            ->values()
            ->all();

        if (empty($scale)) {
            return back()->withErrors(['report_card.scale' => 'Le barème doit contenir au moins une appréciation.'])->withInput();
        }

        $documents = [
            'header' => array_map(fn ($v) => trim((string) $v), $validated['header'] ?? []) + DocumentSettings::DEFAULTS['header'],
            'branding' => array_merge($current['branding'], [
                'primary_color' => $validated['branding']['primary_color'],
                'accent_color' => $validated['branding']['accent_color'],
                'signatory_title' => $validated['branding']['signatory_title'] ?? '',
                'signatory_name' => $validated['branding']['signatory_name'] ?? '',
            ]),
            'report_card' => array_merge($validated['report_card'], [
                'scale' => $scale,
                'show_rank' => $request->boolean('report_card.show_rank'),
                'show_class_size' => $request->boolean('report_card.show_class_size'),
                'show_council_mention' => $request->boolean('report_card.show_council_mention'),
                'show_stamp' => $request->boolean('report_card.show_stamp'),
            ]),
            'student_card' => array_merge($validated['student_card'], [
                'show_birth_date' => $request->boolean('student_card.show_birth_date'),
                'show_gender' => $request->boolean('student_card.show_gender'),
            ]),
            'receipt' => array_merge($validated['receipt'], [
                'show_pending_installments' => $request->boolean('receipt.show_pending_installments'),
                'show_stamp' => $request->boolean('receipt.show_stamp'),
            ]),
        ];

        foreach (['stamp', 'signature'] as $image) {
            if ($request->boolean("remove_{$image}") && $current['branding'][$image]) {
                Storage::disk('public')->delete($current['branding'][$image]);
                $documents['branding'][$image] = null;
            }

            if ($request->hasFile($image)) {
                if ($current['branding'][$image]) {
                    Storage::disk('public')->delete($current['branding'][$image]);
                }
                $documents['branding'][$image] = $request->file($image)->store("schools/{$school->id}/documents", 'public');
            }
        }

        $settings = $school->settings ?? [];
        $settings['documents'] = $documents;
        $school->settings = $settings;
        $school->save();

        return redirect()->route('app.settings.documents')
            ->with('success', 'Paramètres des documents enregistrés.');
    }

    /**
     * Aperçu PDF d'un document avec des données fictives (rien n'est lu ni écrit en base
     * hors l'école courante), pour juger du rendu avant de générer de vrais documents.
     */
    public function preview(string $type)
    {
        $school = School::findOrFail(session('current_school_id'));

        $student = (new Student)->forceFill([
            'first_name' => 'Aya Marie', 'last_name' => 'Exemple', 'matricule' => 'EX-0001',
            'birth_date' => '2016-03-14', 'gender' => 'F',
        ]);
        $schoolClass = (new SchoolClass)->forceFill(['name' => 'CM1 A']);
        $schoolYear = (new SchoolYear)->forceFill(['name' => now()->format('Y').'-'.now()->addYear()->format('Y')]);

        [$view, $data, $paper] = match ($type) {
            'report_card' => ['pdf.report-card', $this->sampleReportCard($school), ['a4', 'portrait']],
            'student_card' => ['pdf.student-card', ['currentClassName' => $schoolClass->name, 'qrImageData' => null], null],
            'receipt' => ['pdf.receipt', $this->sampleReceipt(), null],
            default => abort(404),
        };

        $pdf = Pdf::loadView($view, $data + compact('school', 'student', 'schoolClass', 'schoolYear'));
        if ($paper) {
            $pdf->setPaper(...$paper);
        }

        return $pdf->stream("apercu_{$type}.pdf");
    }

    /**
     * Bulletin fictif, non enregistré, pour l'aperçu.
     */
    private function sampleReportCard(School $school): array
    {
        $subjects = collect([
            ['Français', 3, 15.5], ['Mathématiques', 3, 17], ['Sciences', 2, 12.25],
            ['Histoire-Géographie', 2, 9.5], ['Anglais', 1, 18.5],
        ])->map(fn ($s, $i) => (new Subject)->forceFill(['id' => $i + 1, 'name' => $s[0], 'coefficient' => $s[1], 'max_score' => 20]));

        $grades = collect([15.5, 17, 12.25, 9.5, 18.5])->map(fn ($score, $i) => (new Grade)->forceFill([
            'subject_id' => $i + 1, 'score' => $score, 'max_score' => 20, 'coefficient_used' => $subjects[$i]->coefficient,
        ]));

        $reportCard = (new ReportCard)->forceFill([
            'period' => 'trimestriel', 'quarter' => 1, 'rank' => 4, 'total_students' => 32,
            'teacher_comment' => 'Bon trimestre, des efforts à poursuivre en histoire-géographie.',
            'director_comment' => 'Élève sérieuse et appliquée.',
        ])->setRelation('grades', $grades)->setRelation('nextSchoolClass', null);

        return ['reportCard' => $reportCard, 'allSubjects' => $subjects];
    }

    /**
     * Reçu de paiement fictif, non enregistré, pour l'aperçu.
     */
    private function sampleReceipt(): array
    {
        $payment = (new Payment)->forceFill([
            'id' => 123, 'payment_date' => now()->toDateString(), 'amount' => 50000, 'payment_method' => 'espèces',
        ])->setRelation('studentInstallment', null);

        $pendingInstallments = collect([
            ['2e tranche scolarité', now()->addMonth(), 75000],
            ['3e tranche scolarité', now()->addMonths(3), 75000],
        ])->map(fn ($i) => (new StudentInstallment)->forceFill([
            'description' => $i[0], 'due_date' => $i[1]->toDateString(), 'amount' => $i[2], 'paid_amount' => 0,
        ]));

        return ['payment' => $payment, 'pendingInstallments' => $pendingInstallments, 'userName' => auth()->user()->full_name];
    }
}
