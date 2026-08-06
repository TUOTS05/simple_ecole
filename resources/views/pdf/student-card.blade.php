<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Carte scolaire — {{ $student->matricule }}</title>
    <style>
        @page { size: 85.6mm 54mm; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #172033; }
        .card { width: 85.6mm; height: 54mm; position: relative; overflow: hidden; background: #fff; border: .45mm solid #143b74; }
        .top-band { height: 13mm; padding: 2.5mm 4mm; color: #fff; background: #123b76; }
        .top-band:after { content: ''; position: absolute; top: 0; right: -8mm; width: 34mm; height: 13mm; background: #e8a317; transform: skewX(-28deg); }
        .school-name { position: relative; z-index: 2; width: 56mm; font-size: 10px; line-height: 1.2; font-weight: bold; text-transform: uppercase; }
        .card-type { position: relative; z-index: 2; margin-top: 1mm; font-size: 6.4px; letter-spacing: .55px; font-weight: bold; }
        .body { padding: 3mm 4mm 2mm; }
        .content { width: 100%; border-collapse: collapse; }
        .photo-cell { width: 20mm; vertical-align: top; }
        .photo { width: 17mm; height: 21mm; border: .35mm solid #d9e2ef; background: #eef3f8; text-align: center; overflow: hidden; }
        .photo img { width: 17mm; height: 21mm; object-fit: cover; }
        .photo-placeholder { padding-top: 7.5mm; color: #6b7a90; font-size: 6px; font-weight: bold; }
        .details-cell { vertical-align: top; padding-left: 1.5mm; }
        .student-name { margin-bottom: 1.8mm; color: #123b76; font-size: 11px; line-height: 1.05; font-weight: bold; text-transform: uppercase; }
        .details { width: 100%; border-collapse: collapse; font-size: 6.7px; }
        .details td { padding: .45mm 0; vertical-align: top; }
        .label { width: 18mm; color: #62718a; font-weight: bold; }
        .value { color: #172033; font-weight: bold; }
        .bottom { position: absolute; bottom: 0; left: 0; right: 0; height: 13mm; padding: 2mm 4mm; background: #f2f6fb; border-top: .25mm solid #d5dfec; }
        .bottom-table { width: 100%; border-collapse: collapse; }
        .bottom-table td { vertical-align: middle; }
        .matricule-label { color: #62718a; font-size: 5.5px; font-weight: bold; letter-spacing: .35px; }
        .matricule { margin-top: .6mm; color: #123b76; font-size: 9px; font-weight: bold; letter-spacing: .35px; }
        .validity { margin-top: .7mm; color: #56657a; font-size: 5.5px; }
        .qr-cell { width: 12mm; text-align: right; }
        .qr { width: 10.5mm; height: 10.5mm; padding: .5mm; background: #fff; border: .25mm solid #ccd7e5; }
        .qr img { display: block; width: 9.5mm; height: 9.5mm; }
        .verify { margin-top: .35mm; color: #62718a; font-size: 4.5px; text-align: right; }
    </style>
</head>
<body>
    @php
        $photoPath = $student->photo && file_exists(storage_path('app/public/' . $student->photo))
            ? storage_path('app/public/' . $student->photo)
            : null;
        $schoolYear = now()->format('Y') . '-' . now()->addYear()->format('Y');
        $studentName = trim(($student->last_name ?? '') . ' ' . ($student->first_name ?? ''));
        $qrPayload = $qrData ?? ($student->matricule ?? 'INCONNU');
    @endphp

    <div class="card">
        <div class="top-band">
            <div class="school-name">{{ $school->name ?? 'Établissement scolaire' }}</div>
            <div class="card-type">CARTE D'IDENTITÉ SCOLAIRE</div>
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
                            <tr><td class="label">Né(e) le</td><td class="value">{{ $student->birth_date ? \Carbon\Carbon::parse($student->birth_date)->format('d/m/Y') : 'Non renseigné' }}</td></tr>
                            <tr><td class="label">Sexe</td><td class="value">{{ $student->gender === 'M' ? 'Masculin' : ($student->gender === 'F' ? 'Féminin' : 'Non renseigné') }}</td></tr>
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
                        <div class="validity">Valable pour l'année scolaire {{ $schoolYear }}</div>
                    </td>
                    <td class="qr-cell">
                        <div class="qr">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($qrPayload) }}" alt="QR code">
                        </div>
                        <div class="verify">Vérification</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
