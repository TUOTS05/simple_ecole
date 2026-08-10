<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>État des Impayés par Classe</title>
    <style>
        @page {
            margin: 60px 40px 40px 80px; /* Marge basse pour le footer fixe */
        }
        
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 11px; 
            color: #000;
        }
        
        /* HEADER : Logo et Titre */
        .header { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            margin-bottom: 15px; 
            border-bottom: 2px solid #0a0a0b;
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

        /* Infos (Date / Utilisateur) alignées à droite */
        .info-table {
            width: 100%;
            border: none;
            margin-bottom: 20px;
        }
        .info-table td {
            border: none;
            padding: 0;
            font-size: 11px;
            color: #333;
        }
        .text-right { text-align: right; }

        /* TABLEAU DES STATISTIQUES GLOBALES (4 colonnes) */
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

        /* TABLEAU PRINCIPAL DES CLASSES */
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
        .text-right { text-align: right; }
        
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
                <h2>État des Impayés par Classe</h2>
                <div class="school-year">Année Scolaire {{ $schoolYear->name ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <!-- Date et Utilisateur alignés à droite -->
    <table class="info-table">
        <tr>
            <td style="width: 50%;"></td>
            <td style="width: 50%;" class="text-right">
                <strong>Date d'export :</strong> {{ now()->format('d/m/Y à H:i') }}<br>
                <strong>Édité par :</strong> {{ $userName }}
            </td>
        </tr>
    </table>

    <!-- Statistiques Globales -->
    <table class="stats-table">
        <tr>
            <td>
                <span class="stats-label">Total Attendu</span>
                <span class="stats-value">{{ number_format($globalStats->total_expected, 0, ',', ' ') }} FCFA</span>
            </td>
            <td>
                <span class="stats-label">Total Encaissé</span>
                <span class="stats-value text-green">{{ number_format($globalStats->total_paid, 0, ',', ' ') }} FCFA</span>
            </td>
            <td>
                <span class="stats-label">Total Impayé</span>
                <span class="stats-value text-red">{{ number_format($globalStats->total_unpaid, 0, ',', ' ') }} FCFA</span>
            </td>
            <td>
                <span class="stats-label">Taux Recouvrement</span>
                <span class="stats-value text-blue">{{ $globalStats->recovery_rate }}%</span>
            </td>
        </tr>
    </table>

    <!-- Tableau des Classes -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">N°</th>
                <th style="width: 30%;">Classe</th>
                <th style="width: 10%;" class="text-center">Élèves</th>
                <th style="width: 18%;" class="text-right">Total Attendu</th>
                <th style="width: 18%;" class="text-right">Total Payé</th>
                <th style="width: 19%;" class="text-right">Total Impayé</th>
            </tr>
        </thead>
        <tbody>
            @foreach($classes as $index => $class)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $class->class_name }}</td>
                <td class="text-center">{{ $class->total_students }}</td>
                <td class="text-right">{{ number_format($class->total_expected, 0, ',', ' ') }} FCFA</td>
                <td class="text-right text-green">{{ number_format($class->total_paid, 0, ',', ' ') }} FCFA</td>
                <td class="text-right text-red">{{ number_format($class->total_unpaid, 0, ',', ' ') }} FCFA</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Document généré automatiquement par le système de gestion scolaire
    </div>
</body>
</html>