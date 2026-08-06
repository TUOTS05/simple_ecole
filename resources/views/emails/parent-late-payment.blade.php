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
                    <!-- En-tête -->
                    <tr>
                        <td style="background-color: #dc2626; padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">Rappel de paiement</h1>
                        </td>
                    </tr>
                    
                    <!-- Corps du message -->
                    <tr>
                        <td style="padding: 30px;">
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">Bonjour,</p>
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">
                                Nous vous informons qu'une échéance de scolarité pour votre enfant, <strong>{{ $student->first_name }} {{ $student->last_name }}</strong> (Classe : {{ $student->enrollments->first()->schoolClass->name ?? 'N/A' }}), est en retard.
                            </p>
                            
                            <table style="width: 100%; border-collapse: collapse; margin: 20px 0; background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 15px; border-bottom: 1px solid #fecaca; font-weight: bold; color: #991b1b;">Détail de l'échéance</td>
                                    <td style="padding: 15px; border-bottom: 1px solid #fecaca; color: #991b1b;"></td>
                                </tr>
                                <tr>
                                    <td style="padding: 15px; color: #333333;">Description</td>
                                    <td style="padding: 15px; color: #333333;">{{ $installment->description }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 15px; color: #333333;">Date d'échéance</td>
                                    <td style="padding: 15px; color: #333333;">{{ \Carbon\Carbon::parse($installment->due_date)->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 15px; color: #333333;">Montant dû</td>
                                    <td style="padding: 15px; font-weight: bold; color: #dc2626;">{{ number_format($installment->amount, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            </table>

                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">
                                Afin d'éviter toute interruption dans le suivi scolaire de votre enfant, nous vous invitons à régulariser cette situation dans les plus brefs délais auprès de l'administration de l'école.
                            </p>
                            
                            <p style="font-size: 14px; color: #666666; line-height: 1.6; margin-top: 30px;">
                                Si vous avez déjà effectué ce paiement, merci de ne pas tenir compte de ce message ou de nous envoyer votre preuve de paiement.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Pied de page -->
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