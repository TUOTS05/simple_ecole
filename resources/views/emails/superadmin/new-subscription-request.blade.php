<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #2563eb, #16a34a); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 30px; border-radius: 0 0 8px 8px; border: 1px solid #e5e7eb; }
        .details { background: white; border-left: 4px solid #2563eb; padding: 15px; margin: 20px 0; }
        .details p { margin: 6px 0; }
        .btn { display: inline-block; background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 10px; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔔 Nouvelle demande d'essai</h1>
        </div>

        <div class="content">
            @php($director = $subRequest->school->users->firstWhere('role', 'school_admin'))

            <p>Une nouvelle demande de création de compte vient d'être soumise depuis la page de démonstration.</p>

            <div class="details">
                <p><strong>🏫 École :</strong> {{ $subRequest->school->name }}</p>
                <p><strong>📍 Adresse :</strong> {{ $subRequest->school->address }}</p>
                <p><strong>📞 Téléphone :</strong> {{ $subRequest->school->phone }}</p>
                @if($director)
                <p><strong>👤 Responsable :</strong> {{ $director->first_name }} {{ $director->last_name }} ({{ $director->email }})</p>
                @endif
                <p><strong>📦 Plan demandé :</strong> {{ $subRequest->plan->name }} ({{ $subRequest->duration === 'yearly' ? 'Annuel' : 'Mensuel' }})</p>
            </div>

            <p style="text-align: center;">
                <a href="{{ route('superadmin.subscriptions.pending') }}" class="btn">Traiter la demande</a>
            </p>
        </div>

        <div class="footer">
            <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre directement.</p>
            <p>&copy; {{ date('Y') }} Simple Ecole. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
