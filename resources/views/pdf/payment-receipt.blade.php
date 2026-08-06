<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Reçu de Paiement #{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</title>
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
            width: 25%;
            text-align: left;
            vertical-align: top;
        }

        .header-logo img {
            max-width: 50px;
            max-height: 50px;
        }

        .header-info {
            width: 75%;
            text-align: right;
            vertical-align: top;
        }

        .school-name {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .school-details {
            font-size: 9px;
            margin-top: 4px;
        }

        .title-section {
            text-align: right;
            margin: 15px 0 10px 0;
            font-size: 11px;
            font-weight: bold;
        }

        .title-section .line {
            display: inline-block;
            width: 150px;
            border-bottom: 1px solid #000;
            margin-left: 5px;
        }

        .form-label {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }

        .form-value {
            display: inline-block;
            border-bottom: 1px solid #000;
            min-width: 100px;
            height: 14px;
            margin-left: 4px;
        }

        .form-value.long {
            min-width: 250px;
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
    $installment = $payment->studentInstallment ?? null;
    $typePaiement = $installment ? ($installment->type === 'registration' ? 'Frais d\'inscription' : 'Frais de scolarité') : 'Frais divers';
    $motif = $installment ? $installment->description : 'Frais scolaires';
    @endphp

    <!-- 1. EN-TÊTE -->
    <table>
        <tr>
            <td class="header-logo">
                @if($school && $school->logo_path && file_exists(public_path('storage/' . $school->logo_path)))
                <img src="{{ public_path('storage/' . $school->logo_path) }}" alt="Logo">
                @else
                <div style="font-size: 9px; color: #666; border: 1px dashed #999; padding: 5px; display: inline-block;">[ LOGO ]</div>
                @endif
            </td>
            <td class="header-info">
                <div class="school-name">{{ $school->name ?? 'NOM DE L\'ÉTABLISSEMENT' }}</div>
                <div class="school-details">
                    Adresse: {{ $school->address ?? 'BP 1234 Abidjan- Côte d\'Ivoire' }} Tél: {{ $school->phone ?? '+225 01 53 91 91 75/ 07 89 57 32 72' }}
                </div>
            </td>
        </tr>
    </table>

    <!-- 2. TITRE -->
    <div class="title-section">
        REÇU DE PAIEMENT N°: <span class="line">{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</span>
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
                <span class="form-value">{{ $userName ?? 'Le Comptable' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="form-label">CLASSE:</span>
                <span class="form-value">{{ $schoolClass->name ?? 'Non assignée' }}</span>
            </td>
            <td>
                <span class="form-label">TYPE P:</span>
                <span class="form-value">{{ $typePaiement }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="form-label">ÉCHÉANCE:</span>
                <span class="form-value long">{{ $motif }} {{ $schoolYear ? '- ' . $schoolYear->name : '' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="form-label">MONTANT:</span>
                <span class="form-value">{{ number_format($payment->amount, 0, ',', ' ') }}</span> FCFA
            </td>
            <td>
                <span class="form-label">MODE:</span>
                <span class="form-value">{{ ucfirst($payment->payment_method ?? 'Espèces') }}</span>
            </td>
        </tr>
    </table>

    <!-- 4. TABLEAU ÉCHÉANCES -->
    <div class="installments-title">ÉCHÉANCE(S) RESTANTE(S)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 50%;">LIBELLÉ/ MOTIF</th>
                <th style="width: 25%;">DATE LIMITE</th>
                <th style="width: 25%;">RESTE À PAYER</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($pendingInstallments) && $pendingInstallments->isNotEmpty())
            @foreach($pendingInstallments as $inst)
            <tr>
                <td>{{ $inst->description }}</td>
                <td style="text-align: center;">{{ \Carbon\Carbon::parse($inst->due_date)->format('d/m/Y') }}</td>
                <td style="text-align: right;">{{ number_format($inst->amount - $inst->paid_amount, 0, ',', ' ') }} FCFA</td>
            </tr>
            @endforeach
            @for($i = $pendingInstallments->count(); $i < 4; $i++)
                <tr>
                <td style="height: 18px;"></td>
                <td></td>
                <td></td>
                </tr>
                @endfor
                @else
                <tr>
                    <td style="height: 18px;"></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td style="height: 18px;"></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td style="height: 18px;"></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td style="height: 18px;"></td>
                    <td></td>
                    <td></td>
                </tr>
                @endif
        </tbody>
    </table>

    <!-- 5. SIGNATURE -->
    <div class="signature-label">Signature/ Cachet</div>
    <div class="signature-box"></div>

    <!-- 6. FOOTER -->
    <div class="footer">MERCI POUR VOTRE CONFIANCE!</div>
</body>

</html>