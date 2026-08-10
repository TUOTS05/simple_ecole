<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Détail des paiements par classe</title>
    <style>
        @page {
            margin: 60px 40px 40px 80px;
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
            border-bottom: 2px solid #171717;
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

        /* Infos alignées gauche / droite */
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
        .text-right { text-align: right; }

        /* ✅ NOUVEAU : TABLEAU DES STATISTIQUES GLOBALES */
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        .stats-table td {
            border: 1px solid #000;
            padding: 10px 5px;
            text-align: center;
            vertical-align: middle;
            background-color: #f9fafb;
            width: 25%;
        }
        .stats-label {
            font-size: 9px;
            color: #666;
            margin-bottom: 5px;
            display: block;
            text-transform: uppercase;
            font-weight: bold;
        }
        .stats-value {
            font-size: 14px;
            font-weight: bold;
        }
        .text-green { color: #16a34a; }
        .text-red { color: #dc2626; }
        .text-blue { color: #2563eb; }

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
        }
        .data-table th { 
            background-color: #f3f4f6; 
            font-weight: bold; 
            text-align: center; 
        }
        .text-center { text-align: center; }
        
        /* FOOTER FIXE */
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
            <img src="{{ $schoolLogoPath ?? public_path('images/default-logo.png') }}" alt="Logo École" class="logo">
            <div class="header-title">
                <h2>Détail des paiements par classe</h2>
                <div class="school-year">Année Scolaire {{ $schoolYear->name ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <!-- Infos alignées gauche / droite -->
    <table class="info-table">
        <tr>
            <td style="width: 50%;">
                <strong>Classe :</strong> {{ $class->name }} &nbsp;|&nbsp; 
                <strong>Nombre d'élèves :</strong> {{ $students->count() }}
            </td>
            <td style="width: 50%;" class="text-right">
                <strong>Date d'export :</strong> {{ now()->format('d/m/Y à H:i') }}<br>
                <strong>Édité par :</strong> {{ $userName ?? 'Non spécifié' }}
            </td>
        </tr>
    </table>

    <!-- ✅ NOUVEAU : Tableau des statistiques globales de la classe -->
    <table class="stats-table">
        <tr>
            <td>
                <span class="stats-label">Total Dû</span>
                <span class="stats-value">{{ number_format($classStats->total_du, 0, ',', ' ') }} FCFA</span>
            </td>
            <td>
                <span class="stats-label">Total Encaissé</span>
                <span class="stats-value text-green">{{ number_format($classStats->total_paye, 0, ',', ' ') }} FCFA</span>
            </td>
            <td>
                <span class="stats-label">Total Impayé</span>
                <span class="stats-value text-red">{{ number_format($classStats->total_reste, 0, ',', ' ') }} FCFA</span>
            </td>
            <td>
                <span class="stats-label">Taux de Recouvrement</span>
                <span class="stats-value text-blue">{{ $classStats->recovery_rate }}%</span>
            </td>
        </tr>
    </table>

    <!-- Tableau des élèves (structure inchangée) -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">N°</th>
                <th style="width: 20%;">Matricule</th>
                <th style="width: 35%;">Nom et Prénom</th>
                <th style="width: 13%;" class="text-right">Total Dû</th>
                <th style="width: 13%;" class="text-right">Payé</th>
                <th style="width: 14%;" class="text-right">Reste</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $index => $student)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $student->matricule ?? 'N/A' }}</td>
                <td>{{ strtoupper($student->last_name ?? 'N/A') }} {{ ucfirst($student->first_name ?? 'N/A') }}</td>
                <td class="text-right">{{ number_format($student->total_du, 0, ',', ' ') }} FCFA</td>
                <td class="text-right text-green">{{ number_format($student->total_paye, 0, ',', ' ') }} FCFA</td>
                <td class="text-right text-red">{{ number_format($student->total_reste, 0, ',', ' ') }} FCFA</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Document généré automatiquement par le système de gestion scolaire
    </div>
</body>
</html>