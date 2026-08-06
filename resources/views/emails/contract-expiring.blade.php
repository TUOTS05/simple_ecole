<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background-color: #2c3e50; color: #ffffff; padding: 20px; text-align: center; }
        .header h2 { margin: 0; font-size: 20px; }
        .content { padding: 30px; }
        .content p { margin-bottom: 15px; }
        .details { background-color: #f8f9fa; padding: 15px; border-left: 4px solid #3498db; margin: 20px 0; }
        .button { display: inline-block; padding: 12px 24px; background-color: #3498db; color: #ffffff !important; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 10px; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #7f8c8d; background-color: #ecf0f1; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>⚠️ Rappel d'expiration d'abonnement</h2>
        </div>
        <div class="content">
            <p>Bonjour <strong>{{ $contract->school->name }}</strong>,</p>
            <p>Nous tenons à vous informer que votre contrat d'abonnement à notre plateforme (<strong>N° {{ $contract->contract_number }}</strong>) arrive à expiration dans <strong>30 jours</strong>.</p>
            
            <div class="details">
                <p style="margin: 0;"><strong>Plan actuel :</strong> {{ $contract->plan_name }}</p>
                <p style="margin: 5px 0 0;"><strong>Date d'expiration :</strong> {{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }}</p>
            </div>

            <p>Pour éviter toute interruption de service et la perte d'accès à vos données, nous vous invitons à contacter votre administrateur ou à procéder au renouvellement dès que possible.</p>
            
            <p style="text-align: center;">
                <a href="{{ url('/login') }}" class="button">Accéder à mon espace école</a>
            </p>
        </div>
        <div class="footer">
            <p>Ceci est un message automatique généré par {{ config('app.name') }}.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>