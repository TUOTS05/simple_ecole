<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport de Présences</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h2 { text-align: center; color: #2563eb; }
        .info { margin-bottom: 20px; font-size: 11px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f3f4f6; font-weight: bold; }
        .absent { color: #dc2626; font-weight: bold; }
        .late { color: #d97706; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Rapport de Présences et Absences</h2>
    <div class="info">
        <strong>Classe :</strong> {{ $className }} <br>
        <strong>Période :</strong> Du {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }} <br>
        <strong>Date d'export :</strong> {{ now()->format('d/m/Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Période</th>
                <th>Classe</th>
                <th>Élève</th>
                <th>Statut</th>
                <th>Observation</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $row)
            <tr>
                <td>{{ \Carbon\Carbon::parse($row->date)->format('d/m/Y') }}</td>
                <td>{{ $row->period === 'matin' ? 'Matin' : 'Après-midi' }}</td>
                <td>{{ $row->class_name }}</td>
                <td>{{ $row->student_name }}</td>
                <td class="{{ $row->status === 'absent' ? 'absent' : ($row->status === 'late' ? 'late' : '') }}">
                    {{ ucfirst($row->status) }}
                </td>
                <td>{{ $row->notes ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>