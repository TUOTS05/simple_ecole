<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Autorisation de sortie</title>
    <style>
        @page {
            size: A5 portrait;
            margin: 10mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #000;
        }

        .container {
            border: 2px solid #1e40af;
            padding: 15px;
        }

        .school-name {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 10px;
        }

        .title {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            margin: 10px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        td {
            padding: 4px 0;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            width: 40%;
        }

        .statement {
            margin: 20px 0;
            line-height: 1.6;
            text-align: justify;
        }

        .signature-row {
            margin-top: 30px;
        }

        .signature-box {
            border: 1px solid #000;
            height: 50px;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="school-name">{{ $school->name ?? 'Établissement' }}</div>
        <div class="title">Autorisation de sortie scolaire</div>

        <table>
            <tr>
                <td class="label">Sortie :</td>
                <td>{{ $extra->name }}</td>
            </tr>
            @if($extra->destination)
            <tr>
                <td class="label">Destination :</td>
                <td>{{ $extra->destination }}</td>
            </tr>
            @endif
            @if($extra->start_date)
            <tr>
                <td class="label">Date :</td>
                <td>{{ $extra->start_date->format('d/m/Y') }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">Transport :</td>
                <td>{{ $extra->includes_transport ? 'Inclus' : 'Non inclus' }}</td>
            </tr>
            @if($extra->responsible)
            <tr>
                <td class="label">Encadreur responsable :</td>
                <td>{{ $extra->responsible->full_name ?? $extra->responsible->name }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">Élève :</td>
                <td>{{ $student->last_name }} {{ $student->first_name }} ({{ $student->matricule }})</td>
            </tr>
        </table>

        <div class="statement">
            Je soussigné(e), parent ou tuteur légal de l'élève désigné(e) ci-dessus, autorise mon enfant
            à participer à la sortie scolaire mentionnée ci-dessus, organisée par {{ $school->name ?? "l'établissement" }}.
        </div>

        <div class="signature-row">
            <table>
                <tr>
                    <td style="width: 50%;">
                        <strong>Fait le :</strong> ____ / ____ / ________
                    </td>
                    <td style="width: 50%;">
                        <strong>Signature du parent :</strong>
                        <div class="signature-box"></div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>
