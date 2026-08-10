<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>État Financier - {{ $student->first_name }} {{ $student->last_name }}</title>
    <style>
        @page {
            margin: 60px 40px 40px 80px; /* Marge basse pour le footer fixe */
        }
        
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 11px; 
            color: #000;
        }
        
        /* HEADER : Logo et Titre sur la même ligne */
        .header { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            margin-bottom: 15px; 
            border-bottom: 2px solid #0f0f0f;
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
            line-height: 1.6;
        }
        .text-right { text-align: right; }

        /* TABLEAU DES STATISTIQUES */
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
        .text-blue { color: #191a1a; }

        /* TITRES DE SECTION */
        .section-title { 
            font-size: 12px; 
            font-weight: bold; 
            color: #090909; 
            margin-top: 20px; 
            margin-bottom: 5px; 
            border-bottom: 1px solid #111112;
            padding-bottom: 3px;
        }

        /* TABLEAUX DE DONNÉES */
        .data-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 5px; 
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
            <img src="{{ $schoolLogoPath ?? public_path('images/default-logo.png') }}" alt="Logo École" class="logo">
            <div class="header-title">
                <h2>État Financier par Élève</h2>
                <div class="school-year">Année Scolaire {{ $schoolYear->name ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <!-- Infos Élève (gauche) et Export (droite) -->
    <table class="info-table">
        <tr>
            <td style="width: 50%;">
                <strong>Matricule :</strong> {{ $student->matricule ?? 'N/A' }}<br>
                <strong>Nom :</strong> {{ strtoupper($student->last_name ?? 'N/A') }} {{ ucfirst($student->first_name ?? 'N/A') }}<br>
                <strong>Classe :</strong> {{ $enrollment->schoolClass->name ?? 'N/A' }}
            </td>
            <td style="width: 50%;" class="text-right">
                <strong>Date d'export :</strong> {{ now()->format('d/m/Y à H:i') }}<br>
                <strong>Édité par :</strong> {{ $userName ?? 'Non spécifié' }}
            </td>
        </tr>
    </table>

    <!-- Statistiques Globales -->
    <table class="stats-table">
        <tr>
            <td>
                <span class="stats-label">Total Dû</span>
                <span class="stats-value">{{ number_format($totalDue, 0, ',', ' ') }} FCFA</span>
            </td>
            <td>
                <span class="stats-label">Total Payé</span>
                <span class="stats-value text-green">{{ number_format($totalPaid, 0, ',', ' ') }} FCFA</span>
            </td>
            <td>
                <span class="stats-label">Reste à Payer</span>
                <span class="stats-value text-red">{{ number_format($totalRemaining, 0, ',', ' ') }} FCFA</span>
            </td>
            <td>
                <span class="stats-label">Taux de Paiement</span>
                <span class="stats-value text-blue">{{ $paymentRate }}%</span>
            </td>
        </tr>
    </table>

    <!-- Échéances -->
    <div class="section-title">Échéances</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">N°</th>
                <th style="width: 35%;">Description</th>
                <th style="width: 20%;" class="text-right">Montant</th>
                <th style="width: 20%;" class="text-right">Payé</th>
                <th style="width: 20%;" class="text-center">Date Échéance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($installments as $index => $installment)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $installment->description ?? '-' }}</td>
                <td class="text-right">{{ number_format($installment->amount, 0, ',', ' ') }} FCFA</td>
                <td class="text-right text-green">{{ number_format($installment->paid_amount, 0, ',', ' ') }} FCFA</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($installment->due_date)->format('d/m/Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Historique des Paiements -->
    <div class="section-title">Historique des Paiements</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">N°</th>
                <th style="width: 15%;" class="text-center">Date</th>
                <th style="width: 20%;" class="text-right">Montant</th>
                <th style="width: 20%;" class="text-center">Type</th>
                <th style="width: 20%;" class="text-center">Mode</th>
                <th style="width: 20%;" class="text-center">Référence</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $index => $payment)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}</td>
                <td class="text-right text-green">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                <td class="text-center">{{ ucfirst($payment->payment_type ?? '-') }}</td>
                <td class="text-center">{{ ucfirst($payment->payment_method ?? '-') }}</td>
                <td class="text-center">{{ $payment->reference ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">Aucun paiement enregistré</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Document généré automatiquement par le système de gestion scolaire
    </div>
</body>
</html>