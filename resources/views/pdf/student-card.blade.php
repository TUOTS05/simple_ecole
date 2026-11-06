@php
    $doc = \App\Support\DocumentSettings::for($school ?? null);
    $card = $doc['student_card'];
    $primaryColor = $doc['branding']['primary_color'];
    $accentColor = $doc['branding']['accent_color'];
    $logoPath = \App\Support\DocumentSettings::imagePath($school->logo ?? null);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Carte scolaire — {{ $student->matricule }}</title>
    <style>
        /* Page volontairement 1mm plus grande que la carte (85.6x54mm, format ID-1) dans
           chaque dimension : dompdf ajoute une page 2 blanche superflue quand un bloc bordé
           occupe exactement la taille de la page, même sans dépassement visible réel. */
        @page { size: 86.6mm 55mm; margin: 0; }
        /* dompdf ignore box-sizing : chaque hauteur ci-dessous est celle du contenu, marges intérieures
           en plus. Budget vertical de 54mm : bandeau haut 10+4, corps, bandeau bas 10+2. */
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #172033; }
        .card { width: 85.6mm; height: 54mm; position: relative; overflow: hidden; background: #fff; border: .45mm solid {{ $primaryColor }}; }
        .top-band { height: 10mm; padding: 2mm 4mm; overflow: hidden; color: #fff; background: {{ $primaryColor }}; }
        .top-band:after { content: ''; position: absolute; top: 0; right: -8mm; width: 34mm; height: 14mm; background: {{ $accentColor }}; transform: skewX(-28deg); }
        /* Le texte s'arrête avant l'aplat de couleur d'accent, qui commence vers 56mm */
        .top-band-table { position: relative; z-index: 2; width: 52mm; border-collapse: collapse; }
        .top-band-table td { vertical-align: middle; }
        .logo-cell { width: 9mm; padding-right: 1.5mm; }
        .card-logo { width: 8mm; height: 8mm; object-fit: contain; background: #fff; border-radius: 1mm; padding: .3mm; }
        /* Deux lignes au plus pour un nom long, sans déborder du bandeau */
        .school-name { font-size: 8.5px; line-height: 1.15; font-weight: bold; text-transform: uppercase; }
        .card-type { margin-top: .6mm; font-size: 6px; letter-spacing: .55px; font-weight: bold; }
        .body { padding: 2.5mm 4mm 0; }
        .content { width: 100%; border-collapse: collapse; }
        .photo-cell { width: 20mm; vertical-align: top; }
        .photo { width: 17mm; height: 21mm; border: .35mm solid #d9e2ef; background: #eef3f8; text-align: center; overflow: hidden; }
        .photo img { width: 17mm; height: 21mm; object-fit: cover; }
        .photo-placeholder { padding-top: 7.5mm; color: #6b7a90; font-size: 6px; font-weight: bold; }
        .details-cell { vertical-align: top; padding-left: 1.5mm; }
        .student-name { margin-bottom: 1.2mm; color: {{ $primaryColor }}; font-size: 10px; line-height: 1.05; font-weight: bold; text-transform: uppercase; }
        .details { width: 100%; border-collapse: collapse; font-size: 6.5px; }
        .details td { padding: .35mm 0; vertical-align: top; }
        .label { width: 18mm; color: #62718a; font-weight: bold; }
        .value { color: #172033; font-weight: bold; }
        .bottom { position: absolute; bottom: 0; left: 0; right: 0; height: 10mm; padding: 1mm 4mm; background: #f2f6fb; border-top: .25mm solid #d5dfec; }
        .bottom-table { width: 100%; border-collapse: collapse; }
        .bottom-table td { vertical-align: middle; }
        .matricule-label { color: #62718a; font-size: 5.2px; font-weight: bold; letter-spacing: .35px; }
        .matricule { margin-top: .4mm; color: {{ $primaryColor }}; font-size: 8.5px; font-weight: bold; letter-spacing: .35px; }
        .validity { margin-top: .4mm; color: #56657a; font-size: 5.2px; }
        .qr-cell { width: 12mm; text-align: right; }
        .qr { width: 7mm; height: 7mm; padding: .4mm; background: #fff; border: .25mm solid #ccd7e5; }
        .qr img { display: block; width: 7mm; height: 7mm; }
        .verify { margin-top: .2mm; color: #62718a; font-size: 4.2px; text-align: right; }
    </style>
</head>
<body>
    @php
        $photoPath = $student->photo && file_exists(storage_path('app/public/' . $student->photo))
            ? storage_path('app/public/' . $student->photo)
            : null;
        $schoolYear = now()->format('Y') . '-' . now()->addYear()->format('Y');
        $studentName = trim(($student->last_name ?? '') . ' ' . ($student->first_name ?? ''));
    @endphp

    <div class="card">
        <div class="top-band">
            <table class="top-band-table">
                <tr>
                    @if($logoPath)
                    <td class="logo-cell">
                        <img src="{{ $logoPath }}" alt="Logo" class="card-logo">
                    </td>
                    @endif
                    <td>
                        <div class="school-name">{{ $school->name ?? 'Établissement scolaire' }}</div>
                        <div class="card-type">{{ mb_strtoupper($card['title']) }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="body">
            <table class="content">
                <tr>
                    <td class="photo-cell">
                        <div class="photo">
                            @if($photoPath)
                                <img src="{{ $photoPath }}" alt="Photo de l'élève">
                            @else
                                <div class="photo-placeholder">PHOTO<br>ÉLÈVE</div>
                            @endif
                        </div>
                    </td>
                    <td class="details-cell">
                        <div class="student-name">{{ $studentName ?: 'Élève non renseigné' }}</div>
                        <table class="details">
                            <tr><td class="label">Classe</td><td class="value">{{ $currentClassName ?? 'Non assignée' }}</td></tr>
                            @if($card['show_birth_date'])
                            <tr><td class="label">Né(e) le</td><td class="value">{{ $student->birth_date ? \Carbon\Carbon::parse($student->birth_date)->format('d/m/Y') : 'Non renseigné' }}</td></tr>
                            @endif
                            @if($card['show_gender'])
                            <tr><td class="label">Sexe</td><td class="value">{{ $student->gender === 'M' ? 'Masculin' : ($student->gender === 'F' ? 'Féminin' : 'Non renseigné') }}</td></tr>
                            @endif
                            <tr><td class="label">Année</td><td class="value">{{ $schoolYear }}</td></tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <div class="bottom">
            <table class="bottom-table">
                <tr>
                    <td>
                        <div class="matricule-label">MATRICULE ÉLÈVE</div>
                        <div class="matricule">{{ $student->matricule ?? 'NON ATTRIBUÉ' }}</div>
                        <div class="validity">{{ $card['footer_note'] ?: "Valable pour l'année scolaire {$schoolYear}" }}</div>
                    </td>
                    <td class="qr-cell">
                        <div class="qr">
                            @if($qrImageData ?? null)
                                <img src="{{ $qrImageData }}" alt="QR code">
                            @endif
                        </div>
                        <div class="verify">Vérification</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
