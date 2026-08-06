<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Liste des Élèves</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; margin: 20px; }
        h2 { text-align: center; color: #2563eb; margin-bottom: 5px; }
        .info { margin-bottom: 15px; font-size: 10px; color: #555; border-bottom: 2px solid #2563eb; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 10px; }
        th { background-color: #f3f4f6; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Liste des Élèves</h2>
    <div class="info">
        <strong>Classe :</strong> {{ $className ?? 'Toutes' }} |
        <strong>Date :</strong> {{ now()->format('d/m/Y H:i') }} |
        <strong>Total :</strong> {{ $students->count() }} élèves
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Matricule</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Genre</th>
                <th>Date Naissance</th>
                <th>Téléphone</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $index => $student)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $student->matricule ?? 'N/A' }}</td>
                <td>{{ strtoupper($student->last_name ?? 'N/A') }}</td>
                <td>{{ ucfirst($student->first_name ?? 'N/A') }}</td>
                <td>{{ $student->gender ?? 'N/A' }}</td>
                <td>
                    @if(!empty($student->birth_date))
                        {{ \Carbon\Carbon::parse($student->birth_date)->format('d/m/Y') }}
                    @else
                        <em style="color: #999;">Non renseigné</em>
                    @endif
                </td>
                <td>
                    @php
                        $phone = $student->guardian_phone ?: ($student->father_phone ?: ($student->mother_phone ?? null));
                    @endphp
                    @if(!empty($phone))
                        {{ $phone }}
                    @else
                        <em style="color: #999;">Non renseigné</em>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>