<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #2563eb, #16a34a); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 30px; border-radius: 0 0 8px 8px; border: 1px solid #e5e7eb; }
        .credentials { background: white; border-left: 4px solid #2563eb; padding: 15px; margin: 20px 0; }
        .btn { display: inline-block; background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 10px; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 Simple Ecole</h1>
            <p>Votre espace parent est prêt !</p>
        </div>

        <div class="content">
            <p>Bonjour {{ $parentName }},</p>
            <p>Un compte parent a été créé pour vous sur <strong>{{ $schoolName }}</strong>, suite à l'inscription de <strong>{{ $studentName }}</strong>.</p>

            <p>Voici vos identifiants de connexion :</p>

            <div class="credentials">
                <p><strong>📧 Email :</strong> {{ $email }}</p>
                <p><strong>🔑 Mot de passe provisoire :</strong> {{ $password }}</p>
            </div>

            <p style="color: #dc2626; font-size: 14px;">
                ⚠️ <strong>Important :</strong> Pour des raisons de sécurité, nous vous recommandons de changer ce mot de passe dès votre première connexion.
            </p>

            <p style="text-align: center;">
                <a href="{{ $loginUrl }}" class="btn">Accéder à mon espace parent</a>
            </p>

            <p>Depuis cet espace, vous pourrez suivre la scolarité, les paiements et les communications de l'école concernant votre enfant.</p>
            <p>Cordialement,<br>L'équipe {{ $schoolName }}</p>
        </div>

        <div class="footer">
            <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre directement.</p>
            <p>&copy; {{ date('Y') }} Simple Ecole. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
