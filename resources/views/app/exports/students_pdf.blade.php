<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Liste des Élèves</title>
    <style>
        @page {
            margin: 40px 40px 40px 80px; /* Marge basse plus grande pour le footer fixe */
        }
        
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 11px; 
            color: #000;
        }
        
        /* HEADER */
        .header { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            margin-bottom: 15px; 
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logo { 
            width: 60px; 
            height: 60px; 
            object-fit: contain;
        }
        .header-title h2 { 
            color: #131313; 
            font-size: 16px; 
            margin: 0; 
            text-transform: uppercase;
            text-align: center;
        }
        .school-year { 
            font-size: 12px; 
            font-weight: bold; 
            color: #555;
            margin-top: 4px;
        }

        /* INFOS CLASSE / DATE (Alignement gauche/droite garanti) */
        .info-table {
            width: 100%;
            border: none;
            margin-bottom: 15px;
        }
        .info-table td {
            border: none;
            padding: 0;
            font-size: 11px;
            color: #333;
            vertical-align: top;
        }
        .text-right {
            text-align: right;
        }

        /* TABLEAU DES STATISTIQUES */
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }
        .stats-table th, .stats-table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }
        .stats-table th {
            background-color: #f3f4f6;
            font-weight: bold;
        }

        /* TABLEAU PRINCIPAL DES ÉLÈVES */
        .data-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
            font-size: 10px; 
        }
        .data-table th, .data-table td { 
            border: 1px solid #000; 
            padding: 6px; 
            text-align: left; 
        }
        .data-table th { 
            background-color: #f3f4f6; 
            font-weight: bold; 
            text-align: center; 
        }
        
        /* FOOTER FIXE (Comme Word) */
        .footer { 
            position: fixed; 
            bottom: 0; 
            left: 0; 
            right: 0;
            text-align: center; 
            font-size: 10px; 
            color: #666; 
            border-top: 1px solid #ccc;
            padding-top: 8px;
            height: 30px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <img src="{{ $schoolLogoPath }}" alt="Logo École" class="logo">
            <div class="header-title">
                <h2>Liste de Classe</h2>
                <div class="school-year">ANNÉE SCOLAIRE:{{ $schoolYear }}</div>
            </div>
        </div>
    </div>

    <!-- Infos alignées gauche / droite -->
    <table class="info-table">
        <tr>
            <td style="width: 50%;">
                <strong>Classe :</strong> {{ $className }} &nbsp;|&nbsp; 
                <strong>Total :</strong> {{ $totalStudents }} élèves
            </td>
            <td style="width: 50%;" class="text-right">
                <strong>Date :</strong> {{ now()->format('d/m/Y H:i') }}<br>
                <strong>Édité par :</strong> {{ $userName }}
            </td>
        </tr>
    </table>

    <!-- Tableau des statistiques (Masculin/Féminin) -->
    <table class="stats-table">
        <thead>
            <tr>
                <th></th>
                <th>MASCULIN</th>
                <th>FÉMININ</th>
                <th>Taux M</th>
                <th>Taux F</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Nbre :</strong></td>
                <td>{{ $maleCount }}</td>
                <td>{{ $femaleCount }}</td>
                <td>{{ $maleRate }}%</td>
                <td>{{ $femaleRate }}%</td>
            </tr>
        </tbody>
    </table>

    <!-- Tableau des élèves (Colonnes strictement identiques au PDF) -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 20%;">Matricule</th>
                <th style="width: 25%;">Nom</th>
                <th style="width: 20%;">Prénom</th>
                <th style="width: 8%;">Genre</th>
                <th style="width: 22%;">Date Naissance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $index => $student)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $student->matricule ?? 'N/A' }}</td>
                <td>{{ strtoupper($student->last_name ?? 'N/A') }}</td>
                <td>{{ ucfirst($student->first_name ?? 'N/A') }}</td>
                <td>{{ $student->gender === 'M' ? 'M' : ($student->gender === 'F' ? 'F' : 'N/A') }}</td>
                <td>
                    @if(!empty($student->birth_date))
                        {{ \Carbon\Carbon::parse($student->birth_date)->format('d/m/Y') }}
                    @else
                        -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Document généré automatiquement par le système de gestion scolaire
    </div>
</body>
</html>