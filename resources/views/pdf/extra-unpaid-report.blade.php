<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Impayés Extras</title>
    <style>
        @page {
            margin: 12mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .school-name {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .title-main {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0;
        }

        .stats {
            width: 100%;
            margin-bottom: 12px;
        }

        .stats td {
            padding: 6px 10px;
            border: 1px solid #ccc;
            font-size: 10px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #999;
            padding: 4px 6px;
            font-size: 9px;
        }

        .data-table th {
            background-color: #f0f0f0;
            text-transform: uppercase;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .footer {
            margin-top: 15px;
            font-size: 8px;
            color: #666;
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="school-name">{{ $school->name ?? 'Établissement' }}</div>
        <div class="title-main">État des impayés — Extras {{ $schoolYear->name ?? '' }}</div>
    </div>

    <table class="stats">
        <tr>
            <td><strong>Familles concernées :</strong> {{ $globalStats->families_count }}</td>
            <td><strong>Montant total impayé :</strong> {{ number_format($globalStats->total_unpaid, 0, ',', ' ') }} FCFA</td>
            <td><strong>Édité le :</strong> {{ now()->format('d/m/Y à H:i') }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th>Élève</th>
                <th>Matricule</th>
                <th>Extra</th>
                <th class="text-right">Total dû</th>
                <th class="text-right">Payé</th>
                <th class="text-right">Reste</th>
            </tr>
        </thead>
        <tbody>
            @forelse($unpaid as $sub)
            <tr>
                <td>{{ $sub->student->last_name }} {{ $sub->student->first_name }}</td>
                <td>{{ $sub->student->matricule }}</td>
                <td>{{ $sub->extra->name }}</td>
                <td class="text-right">{{ number_format($sub->total_amount, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($sub->paid_amount, 0, ',', ' ') }}</td>
                <td class="text-right"><strong>{{ number_format($sub->remaining_amount, 0, ',', ' ') }}</strong></td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center; padding: 15px;">Aucun impayé.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Document généré automatiquement par Simple École.</div>
</body>

</html>
