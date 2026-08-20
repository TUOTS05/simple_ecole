<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport de Présences</title>
    <style>
        /* Configuration de la page pour laisser de la place au footer fixe */
        @page {
            margin: 80px 80px 40px 80px; /* Haut, Droite, Bas (pour le footer), Gauche */
        }
        
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 12px; 
            color: #000;
        }
        
        /* HEADER : Logo et Titre sur la même ligne */
        .header { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            margin-bottom: 20px; 
            border-bottom: 2px solid #0d0d0e;
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

        /* Tableau de mise en page pour les infos (100% compatible DomPDF) */
        .info-table {
            width: 100%;
            border: none;
            margin-bottom: 20px;
        }
        .info-table td {
            border: none;
            padding: 0;
            vertical-align: top;
            font-size: 11px;
            color: #333;
            line-height: 1.6;
        }
        .text-right {
            text-align: right;
        }
        
        /* Tableau des données */
        .data-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
            font-size: 11px; 
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
        
        .absent { color: #dc2626; font-weight: bold; }
        .late { color: #d97706; font-weight: bold; }
        .present { color: #16a34a; }
        
        /* FOOTER : Fixe en bas de chaque page (comme Word) */
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
            <!-- Logo dynamique avec fallback si aucun logo n'est défini -->
            <img src="{{ $schoolLogoPath ?? public_path('images/default-logo.png') }}" alt="Logo École" class="logo">
            <div class="header-title">
                <h2>Rapport de Présences</h2>
                <div class="school-year">ANNÉE {{ $schoolYear ?? '2025-2026' }}</div>
            </div>
        </div>
    </div>

    <!-- ✅ Utilisation d'un tableau invisible pour un alignement gauche/droite garanti dans DomPDF -->
    <table class="info-table">
        <tr>
            <td style="width: 50%;">
                <strong>Classe :</strong> {{ $className }}<br>
                <strong>Période :</strong> Du {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
            </td>
            <td style="width: 50%;" class="text-right">
                <strong>Date d'export :</strong> {{ now()->format('d/m/Y H:i') }}<br>
                <strong>Enseignant :</strong> {{ $teacherName ?? 'Non spécifié' }}
            </td>
        </tr>
    </table>

    <table class="data-table">
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
                <td>
                    @if($row->period === 'matin')
                        Matin
                    @elseif($row->period === 'apres_midi' || $row->period === 'apres-midi')
                        Après-midi
                    @else
                        {{ ucfirst($row->period) }}
                    @endif
                </td>
                <td>{{ $row->class_name }}</td>
                <td>{{ $row->student_name }}</td>
                <td class="{{ $row->status === 'absent' ? 'absent' : ($row->status === 'late' ? 'late' : 'present') }}">
                    {{ ucfirst($row->status) }}
                </td>
                <td>{{ $row->notes ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Document généré automatiquement par le système de gestion scolaire
    </div>
</body>
</html>