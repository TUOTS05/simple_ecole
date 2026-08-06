<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Impayés par Classe</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; margin: 20px; }
        h2 { text-align: center; color: #2563eb; margin-bottom: 5px; }
        .subtitle { text-align: center; color: #666; font-size: 10px; margin-bottom: 20px; }
        .info { margin-bottom: 15px; font-size: 10px; color: #555; border-bottom: 2px solid #2563eb; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 10px; }
        th { background-color: #f3f4f6; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .green { color: #16a34a; font-weight: bold; }
        .red { color: #dc2626; font-weight: bold; }
        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <h2>État des Impayés par Classe</h2>
    <div class="subtitle">Année Scolaire {{ $schoolYear->name ?? 'N/A' }}</div>
    
    <div class="info">
        <strong>Date d'export :</strong> {{ now()->format('d/m/Y à H:i') }}
    </div>

    <!-- ✅ Statistiques en ligne (tableau HTML compatible DomPDF) -->
    <table style="margin-bottom: 20px;">
        <tr>
            <td style="width: 25%; border: 1px solid #ddd; padding: 10px; text-align: center; vertical-align: middle; background-color: #f9fafb;">
                <div style="font-size: 9px; color: #666; margin-bottom: 5px;">Total Attendu</div>
                <div style="font-size: 14px; font-weight: bold; color: #333;">
                    {{ number_format($globalStats->total_expected, 0, ',', ' ') }} FCFA
                </div>
            </td>
            <td style="width: 25%; border: 1px solid #ddd; padding: 10px; text-align: center; vertical-align: middle; background-color: #f9fafb;">
                <div style="font-size: 9px; color: #666; margin-bottom: 5px;">Total Encaissé</div>
                <div style="font-size: 14px; font-weight: bold; color: #16a34a;">
                    {{ number_format($globalStats->total_paid, 0, ',', ' ') }} FCFA
                </div>
            </td>
            <td style="width: 25%; border: 1px solid #ddd; padding: 10px; text-align: center; vertical-align: middle; background-color: #f9fafb;">
                <div style="font-size: 9px; color: #666; margin-bottom: 5px;">Total Impayé</div>
                <div style="font-size: 14px; font-weight: bold; color: #dc2626;">
                    {{ number_format($globalStats->total_unpaid, 0, ',', ' ') }} FCFA
                </div>
            </td>
            <td style="width: 25%; border: 1px solid #ddd; padding: 10px; text-align: center; vertical-align: middle; background-color: #f9fafb;">
                <div style="font-size: 9px; color: #666; margin-bottom: 5px;">Taux Recouvrement</div>
                <div style="font-size: 14px; font-weight: bold; color: #2563eb;">
                    {{ $globalStats->recovery_rate }}%
                </div>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 25%;">Classe</th>
                <th style="width: 10%;" class="text-center">Élèves</th>
                <th style="width: 20%;" class="text-right">Total Attendu</th>
                <th style="width: 20%;" class="text-right">Total Payé</th>
                <th style="width: 20%;" class="text-right">Total Impayé</th>
            </tr>
        </thead>
        <tbody>
            @foreach($classes as $index => $class)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $class->class_name }}</td>
                <td class="text-center">{{ $class->total_students }}</td>
                <td class="text-right">{{ number_format($class->total_expected, 0, ',', ' ') }} FCFA</td>
                <td class="text-right green">{{ number_format($class->total_paid, 0, ',', ' ') }} FCFA</td>
                <td class="text-right red">{{ number_format($class->total_unpaid, 0, ',', ' ') }} FCFA</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Document généré automatiquement par le système de gestion scolaire
    </div>
</body>
</html>