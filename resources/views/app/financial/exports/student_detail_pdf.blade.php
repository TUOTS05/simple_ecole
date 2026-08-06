<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>État Financier - {{ $student->first_name }} {{ $student->last_name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; margin: 20px; }
        h2 { text-align: center; color: #2563eb; margin-bottom: 5px; }
        .subtitle { text-align: center; color: #666; font-size: 10px; margin-bottom: 20px; }
        .info { margin-bottom: 15px; font-size: 10px; color: #555; border-bottom: 2px solid #2563eb; padding-bottom: 10px; }
        .student-info { margin-bottom: 20px; }
        .student-info td { padding: 5px; }
        .section-title { font-size: 13px; font-weight: bold; color: #2563eb; margin-top: 20px; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
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
    <h2>État Financier de l'Élève</h2>
    <div class="subtitle">Année Scolaire {{ $schoolYear->name ?? 'N/A' }}</div>
    
    <div class="info">
        <strong>Date d'export :</strong> {{ now()->format('d/m/Y à H:i') }}
    </div>

    <!-- Informations de l'élève -->
    <div class="student-info">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; width: 50%;"><strong>Matricule :</strong> {{ $student->matricule ?? 'N/A' }}</td>
                <td style="border: none; width: 50%;"><strong>Classe :</strong> {{ $enrollment->schoolClass->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="border: none;"><strong>Nom :</strong> {{ strtoupper($student->last_name) }} {{ ucfirst($student->first_name) }}</td>
                <td style="border: none;"><strong>Date de naissance :</strong> {{ $student->birth_date ? $student->birth_date->format('d/m/Y') : 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <!-- Statistiques en ligne -->
    <table style="margin-bottom: 20px;">
        <tr>
            <td style="width: 25%; border: 1px solid #ddd; padding: 10px; text-align: center; background-color: #f9fafb;">
                <div style="font-size: 9px; color: #666; margin-bottom: 5px;">Total Dû</div>
                <div style="font-size: 14px; font-weight: bold; color: #333;">
                    {{ number_format($totalDue, 0, ',', ' ') }} FCFA
                </div>
            </td>
            <td style="width: 25%; border: 1px solid #ddd; padding: 10px; text-align: center; background-color: #f9fafb;">
                <div style="font-size: 9px; color: #666; margin-bottom: 5px;">Total Payé</div>
                <div style="font-size: 14px; font-weight: bold; color: #16a34a;">
                    {{ number_format($totalPaid, 0, ',', ' ') }} FCFA
                </div>
            </td>
            <td style="width: 25%; border: 1px solid #ddd; padding: 10px; text-align: center; background-color: #f9fafb;">
                <div style="font-size: 9px; color: #666; margin-bottom: 5px;">Reste à Payer</div>
                <div style="font-size: 14px; font-weight: bold; color: #dc2626;">
                    {{ number_format($totalRemaining, 0, ',', ' ') }} FCFA
                </div>
            </td>
            <td style="width: 25%; border: 1px solid #ddd; padding: 10px; text-align: center; background-color: #f9fafb;">
                <div style="font-size: 9px; color: #666; margin-bottom: 5px;">Taux de Paiement</div>
                <div style="font-size: 14px; font-weight: bold; color: #2563eb;">
                    {{ $paymentRate }}%
                </div>
            </td>
        </tr>
    </table>

    <!-- Échéances -->
    <div class="section-title">Échéances (Installments)</div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <!-- <th style="width: 20%;">Type</th> -->
                <th style="width: 30%;">Description</th>
                <th style="width: 15%;" class="text-right">Montant</th>
                <th style="width: 15%;" class="text-right">Payé</th>
                <th style="width: 15%;" class="text-center">Date Échéance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($installments as $index => $installment)
            <tr>
                <td>{{ $index + 1 }}</td>
                <!-- <td>{{ ucfirst($installment->type) }}</td> -->
                <td>{{ $installment->description ?? '-' }}</td>
                <td class="text-right">{{ number_format($installment->amount, 0, ',', ' ') }} FCFA</td>
                <td class="text-right green">{{ number_format($installment->paid_amount, 0, ',', ' ') }} FCFA</td>
                <td class="text-center">{{ $installment->due_date->format('d/m/Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Historique des Paiements -->
    <div class="section-title">Historique des Paiements</div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 15%;">Date</th>
                <th style="width: 20%;" class="text-right">Montant</th>
                <th style="width: 20%;">Type</th>
                <th style="width: 20%;">Mode</th>
                <th style="width: 20%;">Référence</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $index => $payment)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}</td>
                <td class="text-right green">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                <td>{{ $payment->payment_type ?? '-' }}</td>
                <td>{{ $payment->payment_method ?? '-' }}</td>
                <td>{{ $payment->reference ?? '-' }}</td>
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