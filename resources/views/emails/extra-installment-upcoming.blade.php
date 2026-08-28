<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background-color: #2563eb; padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">Échéance à venir</h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 30px;">
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">Bonjour,</p>
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">
                                Nous vous rappelons qu'une échéance pour le service <strong>{{ $installment->subscription->extra->name }}</strong> souscrit par <strong>{{ $student->first_name }} {{ $student->last_name }}</strong> arrive bientôt à échéance.
                            </p>

                            <table style="width: 100%; border-collapse: collapse; margin: 20px 0; background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 15px; border-bottom: 1px solid #bfdbfe; font-weight: bold; color: #1e40af;">Détail de l'échéance</td>
                                    <td style="padding: 15px; border-bottom: 1px solid #bfdbfe; color: #1e40af;"></td>
                                </tr>
                                <tr>
                                    <td style="padding: 15px; color: #333333;">Service</td>
                                    <td style="padding: 15px; color: #333333;">{{ $installment->subscription->extra->name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 15px; color: #333333;">Période</td>
                                    <td style="padding: 15px; color: #333333;">{{ $installment->period_label }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 15px; color: #333333;">Date d'échéance</td>
                                    <td style="padding: 15px; color: #333333;">{{ \Carbon\Carbon::parse($installment->due_date)->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 15px; color: #333333;">Montant restant dû</td>
                                    <td style="padding: 15px; font-weight: bold; color: #2563eb;">{{ number_format($installment->remaining_amount, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            </table>

                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">
                                Merci de bien vouloir régulariser cette échéance avant la date indiquée pour assurer la continuité du service.
                            </p>

                            <p style="font-size: 14px; color: #666666; line-height: 1.6; margin-top: 30px;">
                                Si vous avez déjà effectué ce paiement, merci de ne pas tenir compte de ce message.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #888888;">
                            &copy; {{ date('Y') }} École. Tous droits réservés.<br>
                            Ce message a été généré automatiquement, merci de ne pas y répondre directement.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
