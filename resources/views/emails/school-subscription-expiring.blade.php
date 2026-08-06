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
                        <td style="background-color: #1e3a8a; padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">Rappel d'abonnement</h1>
                        </td>
                    </tr>
                    
                    <!-- Corps du message -->
                    <tr>
                        <td style="padding: 30px;">
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">Bonjour,</p>
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">
                                Nous vous informons que l'abonnement de votre établissement, <strong>{{ $school->name }}</strong>, arrive à expiration dans <strong>30 jours</strong>.
                            </p>
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">
                                Date d'expiration : <strong>{{ \Carbon\Carbon::parse($school->subscription_end_date)->format('d/m/Y') }}</strong>
                            </p>
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">
                                Afin d'éviter toute interruption de service pour votre école (gestion des élèves, paiements, bulletins, etc.), nous vous invitons à renouveler votre abonnement dès que possible.
                            </p>
                            
                            <!-- Bouton d'action -->
                            <p style="text-align: center; margin-top: 30px; margin-bottom: 30px;">
                                <a href="{{ url('/superadmin/subscriptions') }}" style="background-color: #1e3a8a; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Renouveler mon abonnement</a>
                            </p>
                            
                            <p style="font-size: 14px; color: #666666; line-height: 1.6;">
                                Si vous avez déjà procédé au renouvellement ou si vous avez des questions, n'hésitez pas à contacter notre support.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Pied de page -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #888888;">
                            &copy; {{ date('Y') }} SaaS École. Tous droits réservés.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>