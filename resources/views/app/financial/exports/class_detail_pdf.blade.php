<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Détail - {{ $class->name }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            margin: 20px;
        }

        h2 {
            text-align: center;
            color: #2563eb;
            margin-bottom: 5px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            font-size: 10px;
            margin-bottom: 20px;
        }

        .info {
            margin-bottom: 15px;
            font-size: 10px;
            color: #555;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            font-size: 10px;
        }

        th {
            background-color: #f3f4f6;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .green {
            color: #16a34a;
            font-weight: bold;
        }

        .red {
            color: #dc2626;
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>

<body>
    <h2>Détail des Paiements - {{ $class->name }}</h2>
    <div class="subtitle">Année Scolaire {{ $schoolYear->name ?? 'N/A' }}</div>

    <div class="info">
        <strong>Classe :</strong> {{ $class->name }} |
        <strong>Date d'export :</strong> {{ now()->format('d/m/Y à H:i') }} |
        <strong>Nombre d'élèves :</strong> {{ $students->count() }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 15%;">Matricule</th>
                <th style="width: 25%;">Nom et Prénom</th>
                <th style="width: 18%;" class="text-right">Total Dû</th>
                <th style="width: 18%;" class="text-right">Payé</th>
                <th style="width: 18%;" class="text-right">Reste</th>
            </tr>
        </thead>
                <tbody>
            @foreach($students as $index => $student)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $student->matricule ?? 'N/A' }}</td>
                <!-- Ici, on affiche juste le nom en texte brut pour le PDF -->
                <td>{{ strtoupper($student->last_name) }} {{ ucfirst($student->first_name) }}</td>
                <td class="text-right">{{ number_format($student->total_du, 0, ',', ' ') }} FCFA</td>
                <td class="text-right green">{{ number_format($student->total_paye, 0, ',', ' ') }} FCFA</td>
                <td class="text-right red">{{ number_format($student->total_reste, 0, ',', ' ') }} FCFA</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Document généré automatiquement par le système de gestion scolaire
    </div>
</body>

</html>