<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Reçu Extra N°{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page {
            size: A5 portrait;
            margin: 10mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #000;
        }

        .receipt-container {
            border: 2px solid #1e40af;
            padding: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td,
        th {
            padding: 3px 4px;
            vertical-align: bottom;
        }

        .header-logo {
            width: 20%;
            text-align: left;
            vertical-align: top;
        }

        .header-logo img {
            max-width: 50px;
            max-height: 50px;
        }

        .header-info {
            width: 80%;
            text-align: center;
            vertical-align: top;
        }

        .school-name {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .school-details {
            font-size: 9px;
            line-height: 1.4;
        }

        .title-section {
            text-align: left;
            margin: 15px 0 10px 0;
        }

        .title-main {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .form-label {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }

        .form-value {
            margin-left: 4px;
        }

        .installments-title {
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 15px;
            margin-bottom: 5px;
            font-size: 10px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #000;
            padding: 4px;
            font-size: 9px;
        }

        .data-table th {
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #f0f0f0;
        }

        .signature-label {
            font-weight: bold;
            margin-top: 20px;
            font-size: 10px;
        }

        .signature-box {
            border: 1px solid #000;
            height: 40px;
            margin-top: 4px;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }
    </style>
</head>

<body>

    @php
        $pendingInstallments = $subscription->installments()->whereIn('status', ['pending', 'partial', 'overdue'])->orderBy('due_date')->get();
    @endphp

    <div class="receipt-container">

        <!-- 1. EN-TÊTE -->
        <table>
            <tr>
                <td class="header-logo">
                    @if($school && $school->logo && file_exists(public_path('storage/' . $school->logo)))
                    <img src="{{ public_path('storage/' . $school->logo) }}" alt="Logo">
                    @else
                    <div style="font-size: 9px; color: #666; border: 1px dashed #999; padding: 5px; display: inline-block;">[ LOGO ]</div>
                    @endif
                </td>
                <td class="header-info">
                    <div class="school-name">{{ $school->name ?? 'NOM DE L\'ÉTABLISSEMENT' }}</div>
                    <div class="school-details">
                        Adresse: {{ $school->address ?? 'BP 1234 Abidjan- Côte d\'Ivoire' }}<br>
                        Tél: {{ $school->phone ?? '+225 01 53 91 91 75/ 07 89 57 32 72' }}
                    </div>
                </td>
            </tr>
        </table>

        <!-- 2. TITRE -->
        <div class="title-section">
            <div class="title-main">REÇU EXTRA N°: {{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</div><hr>
        </div>

        <!-- 3. CHAMPS FORMULAIRE -->
        <table>
            <tr>
                <td width="50%">
                    <span class="form-label">DATE:</span>
                    <span class="form-value">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}</span>
                </td>
                <td width="50%">
                    <span class="form-label">ENCAISSÉ PAR:</span>
                    <span class="form-value">{{ $payment->receivedByUser->full_name ?? 'Le Comptable' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="form-label">ÉLÈVE:</span>
                    <span class="form-value">{{ $student->last_name }} {{ $student->first_name }}</span>
                </td>
                <td>
                    <span class="form-label">MATRICULE:</span>
                    <span class="form-value">{{ $student->matricule }}</span>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="form-label">SERVICE:</span>
                    <span class="form-value">{{ $extra->name }} {{ $subscription->schoolYear ? '- '.$subscription->schoolYear->name : '' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="form-label">MONTANT:</span>
                    <span class="form-value">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</span>
                </td>
                <td>
                    <span class="form-label">MODE:</span>
                    <span class="form-value">{{ ucfirst($payment->payment_method ?? 'Espèces') }}</span>
                </td>
            </tr>
        </table>

        <!-- 4. TABLEAU ÉCHÉANCES RESTANTES -->
        @if($pendingInstallments->isNotEmpty())
        <div class="installments-title">ÉCHÉANCE(S) RESTANTE(S)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 50%;">PÉRIODE</th>
                    <th style="width: 25%;">DATE LIMITE</th>
                    <th style="width: 25%;">RESTE À PAYER</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingInstallments as $inst)
                <tr>
                    <td>{{ $inst->period_label }}</td>
                    <td style="text-align: center;">{{ \Carbon\Carbon::parse($inst->due_date)->format('d/m/Y') }}</td>
                    <td style="text-align: right;">{{ number_format($inst->amount - $inst->paid_amount, 0, ',', ' ') }} FCFA</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div style="text-align: center; font-weight: bold; margin-top: 15px; padding: 8px;">
            ✅ Ce service est entièrement soldé !
        </div>
        @endif

        <!-- 5. SIGNATURE -->
        <div class="signature-label">Signature/ Cachet</div>
        <div class="signature-box"></div>

        <!-- 6. FOOTER -->
        <div class="footer">MERCI POUR VOTRE CONFIANCE!</div>

    </div>
</body>

</html>
