<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Facture {{ \Carbon\Carbon::parse($month.'-01')->translatedFormat('F Y') }} — {{ $student->last_name }} {{ $student->first_name }}</title>
    <style>
        @page {
            margin: 15mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
        }

        .school-name {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .title-main {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0;
        }

        .meta {
            width: 100%;
            margin-bottom: 15px;
        }

        .meta td {
            padding: 4px 0;
        }

        .label {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #999;
            padding: 6px 8px;
        }

        .data-table th {
            background-color: #f0f0f0;
            text-transform: uppercase;
            text-align: left;
            font-size: 10px;
        }

        .text-right {
            text-align: right;
        }

        tfoot td {
            font-weight: bold;
            background-color: #f0f0f0;
        }

        .footer {
            margin-top: 20px;
            font-size: 9px;
            color: #666;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="school-name">{{ $school->name ?? 'Établissement' }}</div>
        <div class="title-main">Facture consolidée — {{ \Carbon\Carbon::parse($month.'-01')->translatedFormat('F Y') }}</div>
    </div>

    <table class="meta">
        <tr>
            <td><span class="label">Élève :</span> {{ $student->last_name }} {{ $student->first_name }}</td>
            <td><span class="label">Matricule :</span> {{ $student->matricule }}</td>
            <td><span class="label">Édité le :</span> {{ now()->format('d/m/Y') }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th>Service</th>
                <th>Échéance</th>
                <th class="text-right">Montant</th>
                <th class="text-right">Payé</th>
                <th class="text-right">Reste</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lines as $line)
            <tr>
                <td>{{ $line->service }}</td>
                <td>{{ \Carbon\Carbon::parse($line->due_date)->format('d/m/Y') }}</td>
                <td class="text-right">{{ number_format($line->amount, 0, ',', ' ') }} FCFA</td>
                <td class="text-right">{{ number_format($line->paid, 0, ',', ' ') }} FCFA</td>
                <td class="text-right">{{ number_format($line->remaining, 0, ',', ' ') }} FCFA</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center; padding: 15px;">Aucune échéance pour ce mois.</td></tr>
            @endforelse
        </tbody>
        @if($lines->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="2">TOTAL</td>
                <td class="text-right">{{ number_format($totals->amount, 0, ',', ' ') }} FCFA</td>
                <td class="text-right">{{ number_format($totals->paid, 0, ',', ' ') }} FCFA</td>
                <td class="text-right">{{ number_format($totals->remaining, 0, ',', ' ') }} FCFA</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">Document généré automatiquement par Simple École — cette facture regroupe des échéances gérées et réglées séparément par service.</div>
</body>

</html>
