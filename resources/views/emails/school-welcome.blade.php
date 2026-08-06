<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue sur {{ config('app.name') }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .header p {
            margin: 10px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .content {
            padding: 40px 30px;
        }
        .welcome-message {
            font-size: 18px;
            color: #555;
            margin-bottom: 30px;
        }
        .credentials-box {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .credentials-box h3 {
            margin: 0 0 15px 0;
            color: #667eea;
            font-size: 16px;
        }
        .credential-item {
            margin: 10px 0;
            font-size: 14px;
        }
        .credential-item strong {
            color: #333;
            display: inline-block;
            width: 120px;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            text-decoration: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            margin: 30px 0;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: transform 0.2s;
        }
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        .info-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 25px 0;
            border-radius: 4px;
            font-size: 14px;
        }
        .info-box strong {
            color: #856404;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            font-size: 13px;
            color: #6c757d;
            border-top: 1px solid #e9ecef;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        .divider {
            border: 0;
            border-top: 1px solid #e9ecef;
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        
        <!-- HEADER -->
        <div class="header">
            <h1>🎉 Bienvenue sur {{ config('app.name') }} !</h1>
            <p>Votre espace de gestion scolaire est prêt</p>
        </div>

        <!-- CONTENT -->
        <div class="content">
            
            <p class="welcome-message">
                Bonjour et bienvenue <strong>{{ $school->name }}</strong> !<br><br>
                Nous sommes ravis de vous accueillir sur notre plateforme. Votre compte a été créé avec succès et vous êtes à quelques étapes de commencer à gérer votre école de manière efficace.
            </p>

            <!-- IDENTIFIANTS DE CONNEXION -->
            <div class="credentials-box">
                <h3>🔐 Vos identifiants de connexion</h3>
                <div class="credential-item">
                    <strong>Email :</strong> {{ $school->email }}
                </div>
                <div class="credential-item">
                    <strong>Mot de passe :</strong> {{ $password }}
                </div>
                <p style="margin-top: 15px; font-size: 13px; color: #856404;">
                    ⚠️ <strong>Important :</strong> Changez votre mot de passe après votre première connexion pour sécuriser votre compte.
                </p>
            </div>

            <!-- VALIDATION DU CONTRAT -->
            <div style="text-align: center; margin: 40px 0;">
                <p style="font-size: 16px; color: #555; margin-bottom: 20px;">
                    Pour activer votre compte et commencer à utiliser la plateforme, vous devez accepter nos conditions d'utilisation :
                </p>
                <a href="{{ route('school.validate-contract', $school->validation_token) }}" class="cta-button">
                    ✅ Valider le contrat et activer mon compte
                </a>
                <p style="font-size: 13px; color: #6c757d; margin-top: 15px;">
                    Ou copiez ce lien dans votre navigateur :<br>
                    <span style="word-break: break-all; color: #667eea;">{{ route('school.validate-contract', $school->validation_token) }}</span>
                </p>
            </div>

            <hr class="divider">

            <!-- PROCHAINES ÉTAPES -->
            <div>
                <h3 style="color: #333; font-size: 18px; margin-bottom: 15px;">📋 Prochaines étapes</h3>
                <ol style="padding-left: 20px; color: #555;">
                    <li style="margin-bottom: 10px;">Cliquez sur le bouton ci-dessus pour valider le contrat</li>
                    <li style="margin-bottom: 10px;">Connectez-vous avec vos identifiants</li>
                    <li style="margin-bottom: 10px;">Changez votre mot de passe dans les paramètres</li>
                    <li style="margin-bottom: 10px;">Commencez à configurer votre école (classes, enseignants, élèves)</li>
                </ol>
            </div>

            <!-- SUPPORT -->
            <div class="info-box">
                <strong>💬 Besoin d'aide ?</strong><br>
                Notre équipe de support est disponible pour vous accompagner. Contactez-nous à :<br>
                📧 support@{{ parse_url(config('app.url'), PHP_URL_HOST) }}<br>
                📞 +237 600 00 00 00
            </div>

        </div>

        <!-- FOOTER -->
        <div class="footer">
            <p>
                © {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.<br>
                <a href="{{ config('app.url') }}">Visiter le site</a> | 
                <a href="#">Conditions d'utilisation</a> | 
                <a href="#">Politique de confidentialité</a>
            </p>
            <p style="margin-top: 15px; font-size: 12px; color: #adb5bd;">
                Cet email a été envoyé automatiquement. Merci de ne pas y répondre directement.
            </p>
        </div>

    </div>
</body>
</html>