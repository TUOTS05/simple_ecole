<?php

namespace App\Services;

use App\Models\School;
use App\Models\Student;
use App\Support\GdPngImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class StudentCardService
{
    /**
     * Génère (ou régénère) la carte scolaire PDF de l'élève avec un QR chiffré (utilisé par
     * l'enseignant pour l'appel), l'enregistre sur le disque public et met à jour
     * student.id_card_path. Retourne le chemin du fichier généré.
     */
    public function generate(Student $student, ?School $school, string $currentClassName): string
    {
        $qrToken = Crypt::encryptString('student_card:'.$student->id);

        // dompdf (utilisé pour ces cartes) ne rend pas les balises <svg> inline, et BaconQrCode
        // ne sait générer du PNG qu'avec Imagick — non installé sur ce serveur (seul GD l'est).
        // On rasterise donc nous-mêmes le QR en PNG via un back-end GD maison, puis on l'intègre
        // en data URI dans un <img>, comme le logo et la photo déjà présents sur cette carte.
        $writer = new Writer(new ImageRenderer(new RendererStyle(150), new GdPngImageBackEnd()));
        $qrPng = $writer->writeString($qrToken);
        $qrImageData = 'data:image/png;base64,'.base64_encode($qrPng);

        $cardPdf = Pdf::loadView('pdf.student-card', [
            'student' => $student,
            'school' => $school,
            'qrImageData' => $qrImageData,
            'currentClassName' => $currentClassName,
        ]);

        $cardFileName = 'carte_'.($student->admission_number ?? $student->matricule ?? 'unknown').'.pdf';
        $cardPath = 'student_cards/'.$cardFileName;
        Storage::disk('public')->put($cardPath, $cardPdf->output());

        $student->update(['id_card_path' => $cardPath]);

        return $cardPath;
    }
}
