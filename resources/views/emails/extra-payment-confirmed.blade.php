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
                        <td style="background-color: #16a34a; padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">Paiement enregistré</h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 30px;">
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">Bonjour,</p>
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">
                                Nous vous confirmons la bonne réception de votre paiement pour le service <strong>{{ $payment->subscription->extra->name }}</strong> souscrit par <strong>{{ $student->first_name }} {{ $student->last_name }}</strong>.
                            </p>

                            <table style="width: 100%; border-collapse: collapse; margin: 20px 0; background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 15px; border-bottom: 1px solid #bbf7d0; font-weight: bold; color: #166534;">Détail du paiement</td>
                                    <td style="padding: 15px; border-bottom: 1px solid #bbf7d0; color: #166534;"></td>
                                </tr>
                                <tr>
                                    <td style="padding: 15px; color: #333333;">Service</td>
                                    <td style="padding: 15px; color: #333333;">{{ $payment->subscription->extra->name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 15px; color: #333333;">Montant payé</td>
                                    <td style="padding: 15px; font-weight: bold; color: #16a34a;">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                                </tr>
                                <tr>
                                    <td style="padding: 15px; color: #333333;">Date</td>
                                    <td style="padding: 15px; color: #333333;">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 15px; color: #333333;">Reste à payer</td>
                                    <td style="padding: 15px; font-weight: bold; color: {{ $payment->subscription->remaining_amount > 0 ? '#dc2626' : '#16a34a' }};">
                                        {{ number_format($payment->subscription->remaining_amount, 0, ',', ' ') }} FCFA
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size: 14px; color: #666666; line-height: 1.6; margin-top: 30px;">
                                Vous pouvez télécharger votre reçu depuis votre espace parent, rubrique « Mes extras ».
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
