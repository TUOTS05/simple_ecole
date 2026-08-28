<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Liste des inscrits — Extras</title>
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

        .meta {
            font-size: 9px;
            color: #444;
            margin-bottom: 10px;
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

        .text-center {
            text-align: center;
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
        <div class="title-main">Liste des inscrits — {{ $extraName }} ({{ $schoolYear->name ?? '' }})</div>
    </div>

    <div class="meta">Nombre d'inscriptions : {{ $subscriptions->count() }} — Édité le {{ now()->format('d/m/Y à H:i') }}</div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Élève</th>
                <th>Matricule</th>
                <th>Extra</th>
                <th>Classe</th>
                <th class="text-center">Statut</th>
                <th class="text-right">Total</th>
                <th class="text-right">Payé</th>
                <th class="text-right">Reste</th>
            </tr>
        </thead>
        <tbody>
            @php
            $statusLabels = [
                'requested' => 'Demande', 'pending' => 'En attente', 'validated' => 'Validée',
                'active' => 'Active', 'suspended' => 'Suspendue', 'terminated' => 'Résiliée', 'completed' => 'Terminée',
            ];
            @endphp
            @forelse($subscriptions as $sub)
            <tr>
                <td>{{ $sub->student->last_name }} {{ $sub->student->first_name }}</td>
                <td>{{ $sub->student->matricule }}</td>
                <td>{{ $sub->extra->name }}</td>
                <td>{{ $sub->extraTarif->schoolClass->name ?? 'Toutes classes' }}</td>
                <td class="text-center">{{ $statusLabels[$sub->status] ?? ucfirst($sub->status) }}</td>
                <td class="text-right">{{ number_format($sub->total_amount, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($sub->paid_amount, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($sub->remaining_amount, 0, ',', ' ') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center; padding: 15px;">Aucune inscription trouvée pour ces filtres.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Document généré automatiquement par Simple École.</div>
</body>

</html>
